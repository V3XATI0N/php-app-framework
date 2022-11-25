<?php

$htmlIn = $api_data['html'];

if (isset($api_data['options']['format'])) {
    $format = $api_data['options']['format'];
} else {
    $format = "Letter";
}
if (isset($api_data['options']['orientation'])) {
    $orientation = $api_data['options']['orientation'];
} else {
    $orientation = "P";
}

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => $format,
    'orientation' => $orientation,
    'curlAllowUnsafeSslRequests' => true,
    'curlFollowLocation' => true
]);
$mpdf->debug = true;

ob_start();
include(__DIR__ . '/template_header.php');
$exportHeader = ob_get_clean();

try {
    $mpdf->WriteHTML($exportHeader);
    $mpdf->WriteHTML($htmlIn);
    $mpdf->Output();
} catch (\Mpdf\MpdfException $e) {
    logError($e->getMessage(), 'PDF NARFFFF');
}
