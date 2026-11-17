import os
import sys
import time
import random
import argparse
from playwright.sync_api import sync_playwright

# Define directory to store persistent browser state (cookies, session, cache)
USER_DATA_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "user_data")

def human_delay(min_seconds=1.5, max_seconds=4.0):
    """Introduces a random delay to simulate human reading/clicking behavior."""
    delay = random.uniform(min_seconds, max_seconds)
    print(f"[Info] Waiting for {delay:.2f} seconds...")
    time.sleep(delay)

def setup_context(playwright, headless=False):
    """
    Launches browser with persistent context to preserve cookies and login state.
    Bypasses simple bot detection mechanisms.
    """
    print(f"[Info] Launching browser (Headless: {headless})...")
    
    # Common arguments to appear more like a regular browser
    args = [
        "--disable-blink-features=AutomationControlled",
        "--no-sandbox",
        "--disable-infobars",
    ]
    
    # Persistent context saves cookies/localstorage in the USER_DATA_DIR
    context = playwright.chromium.launch_persistent_context(
        user_data_dir=USER_DATA_DIR,
        headless=headless,
        channel="chrome" if not headless else None, # Use chrome channel if available for realism when headful
        viewport={"width": 1280, "height": 800},
        user_agent="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
        args=args
    )
    
    # Extra stealth step: overwrite navigator.webdriver
    page = context.pages[0] if context.pages else context.new_page()
    page.add_init_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
    
    return context, page

def run_login():
    """Opens browser for the user to log in manually and saves session."""
    with sync_playwright() as p:
        context, page = setup_context(p, headless=False)
        print("\n" + "="*60)
        print("INSTRUCTIONS FOR LOGIN:")
        print("1. A browser window has opened.")
        print("2. Log in manually to your LinkedIn account.")
        print("3. Complete any two-factor auth (CAPTCHA, email OTP).")
        print("4. Once you are successfully logged in and see your home feed,")
        print("   close the browser window or press Ctrl+C in this terminal.")
        print("="*60 + "\n")
        
        try:
            page.goto("https://www.linkedin.com/login")
            # Wait for user to close browser or manual interrupt
            while True:
                page.wait_for_timeout(1000)
        except Exception as e:
            print(f"[Info] Browser session finished or interrupted: {e}")
        finally:
            context.close()
            print("[Success] Session saved successfully!")

def run_dry_run():
    """Runs a quick diagnostic to verify browser launches and loads page."""
    with sync_playwright() as p:
        context, page = setup_context(p, headless=False)
        try:
            print("[Info] Navigating to Wikipedia (Dry Run)...")
            page.goto("https://www.wikipedia.org")
            human_delay(2, 3)
            title = page.title()
            print(f"[Success] Successfully loaded page: '{title}'")
        except Exception as e:
            print(f"[Error] Dry run failed: {e}")
        finally:
            context.close()

def run_scrape(profile_url):
    """Scrapes basic public information from a LinkedIn profile URL."""
    with sync_playwright() as p:
        context, page = setup_context(p, headless=False) # Headless=False is safer for scraping to avoid bot blocks
        try:
            print(f"[Info] Navigating to profile: {profile_url}")
            page.goto(profile_url)
            human_delay(3, 5) # Give page some time to render
            
            # Check if we are redirected to a login wall
            current_url = page.url
            if "login" in current_url or "authwall" in current_url:
                print("[Warning] Redirected to login wall. Please run with --login first to authenticate.")
                return

            print("[Info] Extracting profile details...")
            
            # Extract Name (try different common selectors)
            name = "Not Found"
            name_selectors = [
                "h1.text-heading-xlarge",
                "h1.v-card-fullname",
                "h1.pv-top-card-layout__title",
                ".top-card-layout__title",
                "h1"
            ]
            for selector in name_selectors:
                element = page.locator(selector).first
                if element.is_visible():
                    name = element.text_content().strip()
                    break
            
            # Extract Headline
            headline = "Not Found"
            headline_selectors = [
                "div.text-body-medium",
                "h2.pv-top-card-layout__headline",
                ".top-card-layout__headline",
                "div[data-test-id='headline']"
            ]
            for selector in headline_selectors:
                element = page.locator(selector).first
                if element.is_visible():
                    headline = element.text_content().strip()
                    break
            
            # Extract Location
            location = "Not Found"
            location_selectors = [
                "span.text-body-small.inline.t-black--light",
                "div.pv-text-details__left-panel + div span",
                ".top-card-layout__first-subline .top-card__subline-item"
            ]
            for selector in location_selectors:
                element = page.locator(selector).first
                if element.is_visible():
                    location = element.text_content().strip()
                    break

            # Print results
            print("\n" + "="*40)
            print("SCRAPED PROFILE DETAILS:")
            print(f"Name:     {name}")
            print(f"Headline: {headline}")
            print(f"Location: {location}")
            print("="*40 + "\n")
            
        except Exception as e:
            print(f"[Error] Failed to scrape profile: {e}")
        finally:
            context.close()

def main():
    parser = argparse.ArgumentParser(description="LinkedIn Playwright Automation script in Python.")
    group = parser.add_mutually_exclusive_group(required=False)
    group.add_argument("--login", action="store_true", help="Launch browser to manually log in and save session.")
    group.add_argument("--dry-run", action="store_true", help="Run a simple browser diagnostic.")
    group.add_argument("--scrape", type=str, help="URL of the LinkedIn profile to scrape.")
    
    args = parser.parse_args()
    
    # Ensure user data dir exists
    os.makedirs(USER_DATA_DIR, exist_ok=True)
    
    login = args.login
    dry_run = args.dry_run
    scrape_url = args.scrape
    
    # If no arguments provided, show an interactive menu
    if not (login or dry_run or scrape_url):
        print("\n" + "="*50)
        print("            LINKEDIN AUTOMATION TOOL")
        print("="*50)
        print("1. [Login] Save session (Opens browser for manual login)")
        print("2. [Dry Run] Test Playwright setup (Opens Wikipedia)")
        print("3. [Scrape] Get profile information")
        print("4. Exit")
        print("="*50)
        try:
            choice = input("Select an option (1-4): ").strip()
            if choice == "1":
                login = True
            elif choice == "2":
                dry_run = True
            elif choice == "3":
                url = input("Enter LinkedIn Profile URL to scrape: ").strip()
                if url:
                    scrape_url = url
                else:
                    print("[Error] Profile URL cannot be empty.")
                    return
            elif choice == "4":
                print("Exiting...")
                return
            else:
                print("[Error] Invalid choice.")
                return
        except KeyboardInterrupt:
            print("\nExiting...")
            return
    
    if login:
        run_login()
    elif dry_run:
        run_dry_run()
    elif scrape_url:
        run_scrape(scrape_url)

if __name__ == "__main__":
    main()
