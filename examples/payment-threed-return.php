<?php

use Omnipay\Garantibbva\Helpers\Helper;

require_once __DIR__ . '/../vendor/autoload.php';

if ($_POST['procreturncode'] === '00' && !empty($_POST['hashparams'])) {
    if (Helper::is_hash_valid($_POST, '12345678')) {
        echo '<h1>MESAJ BANKADAN GELİYOR</h1>';
    }
}


echo '<pre>' . print_r($_POST, true) . '</pre>';
