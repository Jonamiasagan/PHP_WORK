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

    $response[] = array(

        "status" => "SUCCESS",

        "cm_ref_no" => $id,

        "cif" => $cif,

        "uid" => $res['uid'],

        "aadhaar_no" =>
            isset($last_aad[$id])
            ? $last_aad[$id]
            : "",

        "customer_name" =>
            $res['customer_name'],

        "father_name" =>
            $res['father_name'],

        "gender" =>
            $res['gender'],

        "dob" =>
            $res['dob'],

        "pan_no" =>
            $res['pan_no'],

        "mobile_no" =>
            $res['mobile_no'],

        "address_line1" =>
            $res['address_line1'],

        "address_line2" =>
            $res['address_line2'],

        "current_address1" =>
            $res['current_address1'],

        "current_address2" =>
            $res['current_address2'],

        "district_name" =>
            $res['district_name'],

        "village_name" =>
            $res['village_name'],

        "state_code" =>
            $res['state_code'],

        "pin_code" =>
            $res['pin_code'],

        "communication_pin_code" =>
            $res['communication_pin_code'],

        "kyc_date" =>
            $res['kyc_date'],

        "verification_name" =>
            $res['verification_name'],

        "verification_designation" =>
            $res['verification_designation'],

        "access_code" =>
            $res['access_code'],

        "additional_kyc_type" =>
            $res['additional_kyc_type'],

        "additional_kyc_number" =>
            $res['additional_kyc_number'],

        "image_path" =>
            $source_abs_path,

        "image_size" =>
            filesize($source_abs_path),

        "base64" =>
            $base64
    );
}

echo json_encode(
    $response,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES
);

echo "\n========== END ==========\n";

?>