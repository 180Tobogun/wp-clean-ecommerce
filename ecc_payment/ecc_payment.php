<?php
/*
Plugin Name: eCommerceConnect test
Plugin URI:
Description: Платежный инструмент
Version: 1.0
Author: UPC

*/
include_once 'functions.php';
// create custom plugin settings menu
add_action('admin_menu', 'ecc_create_menu');

function ecc_create_menu()
{
    //create new top-level menu
    add_menu_page('eCommerceConnect settings', 'eCommerceConnect настройки', 'administrator', __FILE__, 'ecc_settings_page', plugins_url('/images/ecc_logo.giff', __FILE__));

    //call register settings function
    add_action('admin_init', 'register_ecc_settings');
}

function register_ecc_settings()
{
    //register our settings
    register_setting('ecc-settings-group', 'merchant_id');
    register_setting('ecc-settings-group', 'terminal_id');
    register_setting('ecc-settings-group', 'total_amount');
    register_setting('ecc-settings-group', 'currency');
    register_setting('ecc-settings-group', 'alt_total_amount');
    register_setting('ecc-settings-group', 'alt_currency');
    register_setting('ecc-settings-group', 'locale');
    register_setting('ecc-settings-group', 'sd');
    register_setting('ecc-settings-group', 'order_id');
    register_setting('ecc-settings-group', 'purchase_time');
    register_setting('ecc-settings-group', 'purchase_desc');
    register_setting('ecc-settings-group', 'signature');
    register_setting('ecc-settings-group', 'ecc');

}

//проверка активации настроек
function eccCheck($n)
{
    $v = esc_attr(get_option($n));
    return !empty($v) ? 'checked="checked"' : '';
}

//вывод формы оплаты на страницах
function ecc_shortcode($atts)
{

    $merchantID = get_option('merchant_id');
    $terminalID = get_option('terminal_id');
    $totalAmount = 153;
    $orderID = time(get_option('order_id'));
    $currency = 980; // uah currency read docs
    $purchase_time = date("ymdHis");


   // "$merchantID;$terminalID;$purchaseTime;$order_id;980;$totalAmount;aa;";
    //$dataECC = "$merchantID;$terminalID;$purchaseTime;$order_id;980;$totalAmount;aa;";
    //$dataECC = $merchantID . ";" . $terminalID . ";150611110821;" . $orderID . ";" . $currency . ";" . $total_amount . ";aa;";
    $data = "$merchantID;$terminalID;$purchase_time;$orderID;$currency;$totalAmount;aa;";

    $pemFile = __DIR__ . '/keys/' . $merchantID . '.pem';
    $fp = fopen($pemFile, "r");
    $priv_key = fread($fp, 8192);
    fclose($fp);
    $pkeyid =
    openssl_get_privatekey($priv_key);
    openssl_sign( $data , $signature, $pkeyid);
    openssl_free_key($pkeyid);
    $b64sign = base64_encode($signature) ;

    $payments[0] = array('sys_name' => 'eCommerce_Connect', 'param_name' => 'ecc');

    $htm = '<form class="ecc" onsubmit="get_action(this);" action="https://ecg.test.upc.ua/go/enter" method="post">';
    $htm .= '<input class="ecc" type="hidden" name="Version" value="1">';
    $htm .= '<input class="ecc" type="hidden" name="MerchantID" value="' . $merchantID . '" name="merchant_id">';
    $htm .= '<input class="ecc" type="hidden" name="TerminalID" value="' . $terminalID . '" name="terminal_id">';
    //if (!empty($atts['fixprice'])) {
      //  $htm .= '<input type="hidden" name="TotalAmount" value="' . $atts['fixprice'] . '">';
      //  $htm .= 'Введите сумму, грн: ' . $atts['fixprice'] . ' грн.<br />';
    // } else
    $htm .= 'Введите сумму, грн:<br /><input class="total-amount" type="text" name="TotalAmount" value="' . $totalAmount . '"><br /><br />';
    $htm .= '<input class="ecc" type="hidden" name="Currency" value="' . $currency . '">';
    $htm .= '<input class="ecc" type="hidden" name="locale" value="ru">';
    $htm .= '<input class="ecc" type="hidden" name="SD" value="aa">';
    $htm .= '<input class="ecc" type="hidden" name="OrderID" value="' . $orderID . '" name="order_id">';
    $htm .= '<input class="ecc" type="hidden" name="PurchaseTime" value="' . $purchase_time . '" name="purchase_time">';
    $htm .= '<input class="ecc" type="hidden" name="PurchaseDesc" value="tran_test">';
    $htm .= '<input class="ecc" type="hidden" name="Signature" value="' . $b64sign . '" name="signature">';
    $htm .= '<input type="submit" value="Оплатить">';

    $mess = "data:" . "<br>" . $data;
    $mess .= "<br>" . "<br>";
    $mess .= "form:" . "<br>" . $htm;
//
    echo "<script type=\"text/javascript\">function get_action(form) {alert('$mess');}</script>";
//
//
//
//
    $htm .= '</form>';
    $htm .= '<script>jQuery(".total-amount").on("input", function() {
                jQuery(this).attr( "value", jQuery(this).val() );
            });
        </script>';
//    print_r($htm);
//    print_r($data);
//    die;
    return $htm;

}

function ecc_settings_page()
{
//Настройки плагина в админке
    ?>
    <div class="wrap">
        <h2>eCommerce Connect</h2>

        <form method="post" action="options.php">
            <?php settings_fields('ecc-settings-group'); ?>
            <?php do_settings_sections('ecc-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Merchant id</th>
                    <td><input type="text" name="merchant_id"
                               value="<?php echo esc_attr(get_option('merchant_id')); ?>"/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Terminal id</th>
                    <td><input type="text" name="terminal_id"
                               value="<?php echo esc_attr(get_option('terminal_id')); ?>"/>
                    </td>
                </tr>


            </table>

            <fieldset>
                <label>Системы оплаты</label>
                <hr/>
                <table class="form-table">
                    <tr>
                        <th>eCommerce Connect</th>
                        <td><input type="checkbox" name="ecc" <?php echo eccCheck('ecc'); ?> /></td>
                    </tr>

                </table>
            </fieldset>

            <?php submit_button(); ?>

        </form>
    </div>
    <?php
}

//Вывод на страницах через шорткод
add_shortcode('ecc', 'ecc_shortcode');

?>