<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

$root_path_8 = '/var/www/html/MCARE_CKYC_PHP_8/';
$db_config_path = $root_path_8 . "DB_CONFIG/DB_CONFIG.php";
include($db_config_path);

header('Content-Type: application/json');

$response = array();

echo "\n========== START ==========\n";

/* ==========================================
 FETCH ELIGIBLE CIF
 ========================================== */

$req_rec = array();
$req_tmpa = array();
$last_aad = array();

$sql = "
 SELECT
 id,
 cm_ref_no,
 aadhaar_no
 FROM customer_master.ELIGIBLE_CIFS
 WHERE
 id >= 5383415
 AND image_processed='No'
 AND aadhaar_no_status='Yes'
 ORDER BY id DESC
 LIMIT 1
 ";

$query = mysqli_query($nano_con, $sql);

if (!$query) {
    die(json_encode(array(
        "status" => false,
        "message" => mysqli_error($nano_con)
    )));
}

while ($res = mysqli_fetch_assoc($query)) {
    $req_rec[$res['id']] = $res['cm_ref_no'];

    $req_tmpa[$res['id']] =
        "'" . $res['cm_ref_no'] . "'";

    $last_aad[$res['cm_ref_no']] =
        $res['aadhaar_no'];
}

if (count($req_rec) < 1) {
    die(json_encode(array(
        "status" => false,
        "message" => "No Pending Records Found"
    )));
}

$pass = implode(",", $req_tmpa);

/* ==========================================
 FETCH CUSTOMER DETAILS
 ========================================== */

$sql = "
 SELECT

 cm.sequence_id AS id,

 udf_decrypt(cm.unique_id) AS cif,

 udf_decrypt(cm.token_id) AS uid,

 udf_decrypt(cm.customer_name) AS customer_name,

 trim(udf_decrypt(cm.father_husband_name))
 AS father_name,

 date(udf_decrypt(cm.date_of_birth))
 AS dob,

 cm.gender,

 cm.customer_pan_number
 AS pan_no,

 trim(udf_decrypt(cm.mobile_number))
 AS mobile_no,

 cm.ekyc_image_path AS Pht,

 cm.customer_photo_path,

 replace(
 cm.address_line1,
 'NA',
 cm.district_name
 ) AS address_line1,

 replace(
 cm.address_line2,
 'NA',
 cm.district_name
 ) AS address_line2,

 replace(
 cm.communication_address_line1,
 'NA',
 cm.district_name
 ) AS current_address1,

 replace(
 cm.communication_address_line2,
 'NA',
 cm.district_name
 ) AS current_address2,

 trim(vm.village_name)
 AS village_name,

 trim(cm.district_name)
 AS district_name,

 sm.reference_code
 AS state_code,

 cm.pin_code,

 cm.communication_pin_code,

 DATE_FORMAT(
 cm.enrollment_date,
 '%d-%m-%Y'
 ) AS kyc_date,

 trim(um.user_name)
 AS verification_name,

 trim(um.designation)
 AS verification_designation,

 cm.access_code,

 cm.additional_kyc_type,

 cm.additional_kyc_imagepath,

 udf_decrypt(cm.additional_kyc_number)
 AS additional_kyc_number

FROM mCARECBS.customer_master cm

LEFT JOIN mCARECBS.village_master vm
 ON cm.village_id = vm.village_id

LEFT JOIN mCARECBS.state_master sm
 ON cm.state_id = sm.state_id

LEFT JOIN mCARECBS.user_master um
 ON cm.access_code = um.access_code

WHERE cm.sequence_id IN ($pass)
 ";

$query = mysqli_query($mcare_con, $sql);

if (!$query) {
    die(json_encode(array(
        "status" => false,
        "message" => mysqli_error($mcare_con)
    )));
}

while ($res = mysqli_fetch_assoc($query)) {
    $id = $res['id'];
    $cif = $res['cif'];

    $photo = trim($res['Pht']);

    $source_abs_path =
        $mcare_drive_images . $photo;

    $basename =
        basename($source_abs_path);

    $dirname =
        dirname($source_abs_path);

    $image_found = false;

    if (file_exists($source_abs_path)) {
        $image_found = true;
    } else if (
        file_exists(
            $dirname . "/EKYC" . $basename
        )
    ) {
        $source_abs_path =
            $dirname . "/EKYC" . $basename;

        $image_found = true;
    } else if (
        file_exists(
            $mcare_drive_images .
            $res['customer_photo_path']
        )
    ) {
        $source_abs_path =
            $mcare_drive_images .
            $res['customer_photo_path'];

        $image_found = true;
    }

    if (!$image_found) {
        $response[] = array(
            "status" => "FAILED",
            "cm_ref_no" => $id,
            "cif" => $cif,
            "message" => "Image Not Found"
        );

        continue;
    }

    $image_content =
        file_get_contents($source_abs_path);

    if ($image_content === false) {
        $response[] = array(
            "status" => "FAILED",
            "cm_ref_no" => $id,
            "cif" => $cif,
            "message" => "Unable To Read Image"
        );

        continue;
    }

    $base64 =
        base64_encode($image_content);

    // Splitting customer name (if multiple words exist) to map to FNAME, LNAME
    $customer_name = $res['customer_name'];
    $name_parts = explode(' ', trim($customer_name));
    $fname = isset($name_parts[0]) ? $name_parts[0] : '';
    $lname = (count($name_parts) > 1) ? $name_parts[count($name_parts) - 1] : '';
    $mname = (count($name_parts) > 2) ? implode(' ', array_slice($name_parts, 1, -1)) : '';

    $response[] = array(
        "UNIQUE_REQUEST_ID" => str_pad($id, 10, "0", STR_PAD_LEFT),
        "CUSTOMERS_DETAILS" => array(
            "CUSTOMER_ID" => $cif,
            "MOD_FLAG" => "Y",
            "SYSTEM_USE" => array(
                "APPLICATION_TYPE" => "01",
                "BRANCH_CODE" => "2011",
                "CKYCNumber" => "98987867897867"
            ),
            "UPDATION_MODULE" => array(
                "APPLICANT_ENTITY_UPDATE_FLAG" => "02",
                "PERSONAL_ENTITY_DETAILS_UPDATE_FLAG" => "02",
                "ADDRESS_DETAILS_UPDATE_FLAG" => "02",
                "CONTACT_DETAILS_UPDATE_FLAG" => "02",
                "REMARKS_UPDATE_FLAG" => "02",
                "KYC_VERIFICATION_UPDATE_FLAG" => "02",
                "IDENTITY_DETAILS_UPDATE_FLAG" => "02",
                "RELATED_PERSON_DETAILS_UPDATE_FLAG" => "02",
                "IMAGE_DETAILS_UPDATE_FLAG" => "02"
            ),
            "PERSONAL_ENTITY_DETAILS" => array(
                "CONSTI_TYPE" => "01",
                "ACC_TYPE" => "",
                "CKYC_FI_REF_NO" => "1",
                "PREFIX" => "",
                "FNAME" => $fname,
                "MNAME" => $mname,
                "LNAME" => $lname,
                "NAME_OF_THE_ENTITY" => $customer_name,
                "FATHER_SPOUSE_FLAG" => "",
                "FATHER_SPOUSE_PREFIX" => "",
                "FATHER_SPOUSE_FNAME" => $res['father_name'],
                "FATHER_SPOUSE_MNAME" => "",
                "FATHER_SPOUSE_LNAME" => "",
                "MOTHER_PREFIX" => "",
                "MOTHER_FNAME" => "",
                "MOTHER_MNAME" => "",
                "MOTHER_LNAME" => "",
                "GENDER" => $res['gender'],
                "DATE_OF_BIRTH_DATE_OF_INCORPORATION" => $res['dob'],
                "PLACE_OF_INCORPORATION" => $res['village_name'],
                "DATE_OF_COMMENCEMENT_OF_BUSINESS" => "",
                "COUNTRY_OF_INCORPORATION" => "IN",
                "Country_of_residence_as_per_Tax_Laws" => "",
                "IDENTIFICATION_TYPE" => "",
                "TAX_IDENTIFICATION_NO_TIN" => "",
                "TIN_ISSUING_COUNTRY" => "",
                "PAN" => $res['pan_no'],
                "TIN_GST_REG_NO" => "",
                "NUMBER_OF_RELATED_PERSON" => "1",
                "CONSTI_TYPE_OTHERS" => ""
            ),
            "ADDRESS_DETAILS" => array(
                "POI_POA_ADDRESS_LINE1" => $res['address_line1'],
                "POI_POA_ADDRESS_LINE2" => $res['address_line2'],
                "POI_POA_ADDRESS_LINE3" => "",
                "POI_POA_ADDRESS_C_T_V" => $res['district_name'],
                "POI_POA_ADDRESS_DISTRICT" => "",
                "POI_POA_ADDRESS_STATE" => $res['state_code'],
                "POI_POA_ADDRESS_COUNTRY" => "IN",
                "POI_POA_ADDRESS_PINCODE" => $res['pin_code'],
                "ADDRESS_PROOF_POI_POA" => "Aadhar Card",
                "ADDRESS_PROOF_POI_POA_OTHERS" => "",
                "FLAG_POI_POA_ADDR_SAME_CURRENT_ADDR" => "Y",
                "CURRENT_ADDRESS_LINE1" => $res['current_address1'],
                "CURRENT_ADDRESS_LINE2" => $res['current_address2'],
                "CURRENT_ADDRESS_LINE3" => "",
                "CURRENT_ADDRESS_C_T_V" => $res['district_name'],
                "CURRENT_ADDRESS_DISTRICT" => "",
                "CURRENT_ADDRESS_STATE" => $res['state_code'],
                "CURRENT_ADDRESS_COUNTRY" => "IN",
                "CURRENT_ADDRESS_PINCODE" => $res['communication_pin_code'],
                "ADDRESS_PROOF_CURRENT_ADDRESS" => "Aadhar Card",
                "SELF_DECLARATION" => "",
                "PROOF_OF_ADDR_SUB_REG_ADDR_OTHERS" => ""
            ),
            "CONTACT_DETAILS" => array(
                "RESIDENCE_TEL_NO_STDCODE" => "",
                "RESIDENCE_TEL_NO" => "",
                "OFFICE_TEL_NO_STDCODE" => "",
                "OFFICE_TEL_NO" => "",
                "MOBILE_NO_ISDCODE" => "",
                "MOBILE_NO" => $res['mobile_no'],
                "FAX_NO_STDCODE" => "",
                "FAX_NO" => "",
                "EMAIL_ID" => "",
                "MOBILE_NO_2_ISDCODE" => "",
                "MOBILE_NO_2" => "",
                "EMAIL_ID_2" => ""
            ),
            "RELATED_PERSON_DETAILS" => array(
                array(
                    "RELATED_CUSTOMER_ID" => "1",
                    "R_TYPE_OF_RELATIONSHIP" => "Director",
                    "R_KYC_NUMBER" => "45673329873324",
                    "R_PREFIX" => "Mr",
                    "R_FNAME" => "Tarun",
                    "R_MNAME" => "raj",
                    "R_LNAME" => "pal",
                    "RELATED_OTHERS_DOCUMENTS" => "",
                    "POA_SUBMITTED_FOR_CURR_PER_OVERSEAS_ADDR_OTHERS" => "",
                    "R_ADD_DEL_RELATED_PERSON" => "Add",
                    "R_SPOUSE_FATHER_MOTHER_FLAG" => "1",
                    "R_FATH_MOTH_SP_NAME_PREFIX" => "Mr.",
                    "R_FATH_MOTH_SP_FNAME" => "Ratan",
                    "R_FATH_MOTH_SP_MIDDLE_FNAME" => "diraj",
                    "R_FATH_MOTH_SP_LAST_FNAME" => "patel",
                    "R_DOB" => "11-July-1984",
                    "R_GENDER" => "M",
                    "R_NATIONALITY" => "IN",
                    "R_PAN_FORM60" => "",
                    "R_ID_TYPE" => "Voter_Id",
                    "R_ID_NO" => "3423",
                    "R_EKYC_AUTH" => "",
                    "R_OFF_VERIFICATION_AADHAR" => "",
                    "Residential_Status" => "01",
                    "R_POI_POA_ADDR_LINE1" => "D5/203",
                    "R_POI_POA_ADDR_LINE2" => "Kutir Nivas",
                    "R_POI_POA_ADDR_LINE3" => "",
                    "R_POI_POA_ADDR_C_T_V" => "Mumbai",
                    "R_POI_POA_ADDR_DISTRICT" => "",
                    "R_POI_POA_ADDR_PINCODE" => "465789",
                    "R_POI_POA_ADDR_STATE" => "MH",
                    "R_POI_POA_ADDR_COUNTRY" => "IN",
                    "R_PROOF_POI_POA" => "",
                    "R_FLAG_POI_POA_ADDR_SAME_CURR_ADDR" => "Y",
                    "R_CURRENT_ADDR_LINE1" => "",
                    "R_CURRENT_ADDR_LINE2" => "",
                    "R_CURRENT_ADDR_LINE3" => "",
                    "R_CURRENT_ADDR_C_T_V" => "",
                    "R_CURRENT_ADDR_DISTRICT" => "",
                    "R_CURRENT_ADDR_STATE" => "",
                    "R_CURRENT_ADDR_COUNTRY" => "",
                    "R_CURRENT_ADDR_PINCODE" => "",
                    "R_PROOF_CURRENT_ADDR" => "",
                    "R_CURRENT_PROOF_OF_ADDRESS_ID_NO" => "",
                    "R_RESI_TEL_NO_STD" => "",
                    "R_RESI_TEL_NO" => "",
                    "R_OFFICE_TEL_NO_STD" => "",
                    "R_OFFICE_TEL_NO" => "",
                    "R_MOBILE_ISD" => "",
                    "R_MOBILE_NO" => "",
                    "R_EMAIL_ID" => "",
                    "R_SELF_DECLARATION" => "",
                    "DIN" => "43427867",
                    "R_PERSON_TYPE_OTHER_DESC" => "",
                    "R_DATE_OF_DECLARATION" => "",
                    "R_PLACE_OF_DECLARATION" => "",
                    "R_KYC_VERIFICATION_DATE" => "",
                    "R_TYPE_OF_DOC_SUBMITTED" => "02",
                    "R_KYC_VERIFICATION_NAME" => "abc",
                    "R_KYC_VERIFICATION_DESIGNATION" => "manager",
                    "R_KYC_VERIFICATION_BRANCH" => "mumbai",
                    "R_KYC_VERIFICATION_EMP_CODE" => "0001",
                    "R_ORGANISATION_NAME" => "org nm",
                    "R_ORGANISATION_CODE" => "O23",
                    "R_Different_Abled_Status_Flg" => "0",
                    "R_Type_of_Impairment" => "",
                    "R_Percent_of_Impairment" => "",
                    "R_UDID_Number" => ""
                )
            ),
            "RELATED_PERSON_IMAGES_DETAILS" => array(
                array(
                    "R_IMG_PHOTOGRAPH_TYPE" => "JPG",
                    "R_IMG_PHOTOGRAPH" => "",
                    "R_IMG_CURR_POA_TYPE" => "JPG",
                    "R_IMG_CURR_POA" => "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mO0a1mYDAAEMwHIQPFfDQAAAABJRU5ErkJggg=="
                )
            ),
            "IDENTITY_DETAILS" => array(
                array(
                    "IDENT_TYPE" => "01",
                    "IDENT_NUM" => isset($last_aad[$id]) ? $last_aad[$id] : "",
                    "IDPROOF_SUBMITTED" => "01",
                    "IDPROOF_SUB_IMG_TYPE" => "PNG",
                    "IDPROOF_SUB_IMG_DATA" => ""
                )
            ),
            "IMAGE_DETAILS" => array(
                "PHOTOGRAPH_IMG_TYPE" => "JPG",
                "PHOTOGRAPH_IMG_DATA" => $base64
            ),
            "ADDRESS_DETAILS_IMG" => array(
                "ADDRESS_PROOF_POI_POA_NUMBER" => "458545745878",
                "ADDRESS_PROOF_POI_POA_IMG_TYPE" => "JPG",
                "ADDRESS_PROOF_POI_POA_IMGDATA" => "",
                "ADDRESS_PROOF_CURRENT_ADDRESS_NUMBER" => "123456789457",
                "ADDRESS_PROOF_CURRENT_ADDRESS_IMG_TYPE" => "JPG",
                "ADDRESS_PROOF_CURRENT_ADDRESS_IMGDATA" => ""
            ),
            "KYC_VERIFICATION" => array(
                "DATE_OF_DECLARATION" => "",
                "PLACE_OF_DECLARATION" => "",
                "KYC_VERIFICATION_DATE" => $res['kyc_date'],
                "TYPE_OF_DOC_SUBMITTED" => "CertifiedCopies",
                "KYC_VERIFICATION_NAME" => $res['verification_name'],
                "KYC_VERIFICATION_DESIGNATION" => $res['verification_designation'],
                "KYC_VERIFICATION_BRANCH" => "IN1519BR",
                "KYC_VERIFICATION_EMP_CODE" => "101535",
                "ORGANISATION_NAME" => "AU Small Finance Bank LTD",
                "ORGANISATION_CODE" => "IN1519",
                "CUSTOMER_IDENTIFICATION_FLAG" => "1"
            )
        )
    );
}

echo json_encode(
    $response,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES
);

echo "\n========== END ==========\n";

?>