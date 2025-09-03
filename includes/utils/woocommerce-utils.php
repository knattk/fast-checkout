<?php
namespace FastCheckout\Utils;

if (!defined('ABSPATH')) exit;

function get_state_code(string $thaiName): string {
    $map = [
        'อำนาจเจริญ' => 'TH-37',
        'อ่างทอง' => 'TH-15',
        'พระนครศรีอยุธยา' => 'TH-14',
        'กรุงเทพมหานคร' => 'TH-10',
        'บึงกาฬ' => 'TH-38',
        'บุรีรัมย์' => 'TH-31',
        'ฉะเชิงเทรา' => 'TH-24',
        'ชัยนาท' => 'TH-18',
        'ชัยภูมิ' => 'TH-36',
        'จันทบุรี' => 'TH-22',
        'เชียงใหม่' => 'TH-50',
        'เชียงราย' => 'TH-57',
        'ชลบุรี' => 'TH-20',
        'ชุมพร' => 'TH-86',
        'กาฬสินธุ์' => 'TH-46',
        'กำแพงเพชร' => 'TH-62',
        'กาญจนบุรี' => 'TH-71',
        'ขอนแก่น' => 'TH-40',
        'กระบี่' => 'TH-81',
        'ลำปาง' => 'TH-52',
        'ลำพูน' => 'TH-51',
        'เลย' => 'TH-42',
        'ลพบุรี' => 'TH-16',
        'แม่ฮ่องสอน' => 'TH-58',
        'มหาสารคาม' => 'TH-44',
        'มุกดาหาร' => 'TH-49',
        'นครนายก' => 'TH-26',
        'นครปฐม' => 'TH-73',
        'นครพนม' => 'TH-48',
        'นครราชสีมา' => 'TH-30',
        'นครสวรรค์' => 'TH-60',
        'นครศรีธรรมราช' => 'TH-80',
        'น่าน' => 'TH-55',
        'นราธิวาส' => 'TH-96',
        'หนองบัวลำภู' => 'TH-39',
        'หนองคาย' => 'TH-43',
        'นนทบุรี' => 'TH-12',
        'ปทุมธานี' => 'TH-13',
        'ปัตตานี' => 'TH-94',
        'พังงา' => 'TH-82',
        'พัทลุง' => 'TH-93',
        'พะเยา' => 'TH-56',
        'เพชรบูรณ์' => 'TH-67',
        'เพชรบุรี' => 'TH-76',
        'พิจิตร' => 'TH-66',
        'พิษณุโลก' => 'TH-65',
        'แพร่' => 'TH-54',
        'ภูเก็ต' => 'TH-83',
        'ปราจีนบุรี' => 'TH-25',
        'ประจวบคีรีขันธ์' => 'TH-77',
        'ระนอง' => 'TH-85',
        'ราชบุรี' => 'TH-70',
        'ระยอง' => 'TH-21',
        'ร้อยเอ็ด' => 'TH-45',
        'สระแก้ว' => 'TH-27',
        'สกลนคร' => 'TH-47',
        'สมุทรปราการ' => 'TH-11',
        'สมุทรสาคร' => 'TH-74',
        'สมุทรสงคราม' => 'TH-75',
        'สระบุรี' => 'TH-19',
        'สตูล' => 'TH-91',
        'สิงห์บุรี' => 'TH-17',
        'ศรีสะเกษ' => 'TH-33',
        'สงขลา' => 'TH-90',
        'สุโขทัย' => 'TH-64',
        'สุพรรณบุรี' => 'TH-72',
        'สุราษฎร์ธานี' => 'TH-84',
        'สุรินทร์' => 'TH-32',
        'ตาก' => 'TH-63',
        'ตรัง' => 'TH-92',
        'ตราด' => 'TH-23',
        'อุบลราชธานี' => 'TH-34',
        'อุดรธานี' => 'TH-41',
        'อุทัยธานี' => 'TH-61',
        'อุตรดิตถ์' => 'TH-53',
        'ยะลา' => 'TH-95',
        'ยโสธร' => 'TH-35'
    ];
    $name = trim($thaiName);
    return $map[$name] ?? $name;
}


function get_payment_name(string $method): string {
    return match ($method) {
        
        'cod'  => __('เก็บเงินปลายทาง', 'fastcheckout'),
        'bacs' => __('โอนผ่านธนาคาร', 'fastcheckout'),
        'omise' => __('บัตรเครดิตหรือเดบิต', 'fastcheckout'),
        'omise_installment' => __('ผ่อนชำระผ่านบัตรเครดิต', 'fastcheckout'),
        'omise_promptpay' => __('PromtPay', 'fastcheckout'),
        'omise_internetbanking' => __('Internet Banking', 'fastcheckout'),
        'omise_truemoney' => __('True Money', 'fastcheckout'),
        'omise_mobilebanking' => __('Mobile Banking', 'fastcheckout'),
        'omise_alipay' => 'AliPay',
        default => ucfirst($method),
    };
}

/** สถานะตั้งต้นของออเดอร์ตามวิธีจ่าย */
function get_order_status(string $method): string {
    return match ($method) {
        'cod' => 'processing',
        'bacs' => 'on-hold',
        'omise' => 'on-hold',
        'omise_installment' => 'on-hold',
        'omise_promptpay' => 'on-hold',
        'omise_internetbanking' => 'on-hold',
        'omise_truemoney' => 'on-hold',
        'omise_mobilebanking' => 'on-hold',
        'omise_alipay' => 'on-hold',
        default => 'on-hold',
    };
}

/** ชำระแล้วหรือยัง (ให้ Woo สร้างเป็น paid หรือไม่) */
function get_paid_status(string $method): bool {
    return match ($method) {
        'cod' => true,
        'bacs' => false,
        'omise' => false,
        'omise_installment' => false,
        'omise_promptpay' => false,
        'omise_internetbanking' => false,
        'omise_truemoney' => false,
        'omise_mobilebanking' => false,
        'omise_alipay' => false,
        default => false,
    };
}
