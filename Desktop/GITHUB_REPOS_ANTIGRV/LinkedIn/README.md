# LinkedIn Automation

A Python Playwright automation tool to interact with LinkedIn. It uses persistent browser contexts to maintain login sessions and bypass basic bot detection and CAPTCHAs.

## Prerequisites

* Python 3.10 or newer
* `pip` (Python package manager)

## Setup

1. **Create a virtual environment**:
   ```bash
   python3 -m venv .venv
   ```

2. **Activate the virtual environment**:
   * macOS/Linux:
     ```bash
     source .venv/bin/activate
     ```
   * Windows:
     ```cmd
     .venv\Scripts\activate
     ```

3. **Install dependencies**:
   ```bash
   pip install -r requirements.txt
   ```

4. **Install Playwright browsers**:
   ```bash
   playwright install chromium
   ```

## Usage

### 1. Authenticate and Save Session

Since LinkedIn aggressively checks for bots and login credentials, you should log in manually **once** to save your session. 

Run the script with the `--login` flag:
```bash
python3 automation.py --login
```
This opens a non-headless (visible) browser window. Log in manually, solve any security verification (CAPTCHA/email pin), and close the browser once logged in. Your session will be saved in the `user_data/` directory.

### 2. Run Automation

After logging in once, you can run other automation tasks without having to re-authenticate:

* **Dry Run (Verify setup)**:
  ```bash
  python3 automation.py --dry-run
  ```

* **Scrape Profile Information**:
  ```bash
  python3 automation.py --scrape "https://www.linkedin.com/in/some-profile-url/"
  ```
