<?php

// Development tools : Dump and Die untuk debugging
// Penggunaan        : dd('value')
function dd($param = NULL)
{
    if (isset($param)) {
        echo '<pre>'.print_r($param, TRUE).'</pre>';
    }

    die();
}

// Development tools : Only Dump untuk debugging
// Penggunaan        : d('value')
function d($param = NULL)
{
    if (isset($param)) {
        echo '<pre>'.print_r($param, TRUE).'</pre>';
    }
}

function month_indo($month = null) {
    $text = null;
    switch($month) {
    case 1:
        $text = 'Januari';
        break;
    case 2:
        $text = 'Februari';
        break;
    case 3:
        $text = 'Maret';
        break;
    case 4:
        $text = 'April';
        break;
    case 5:
        $text = 'Mei';
        break;
    case 6:
        $text = 'Juni';
        break;
    case 7:
        $text = 'Juli';
        break;
    case 8:
        $text = 'Agustus';
        break;
    case 9:
        $text = 'September';
        break;
    case 10:
        $text = 'Oktober';
        break;
    case 11:
        $text = 'November';
        break;
    case 12:
        $text = 'Desember';
        break;
    default:
        $text = null;
        break;
    }

    return $text;
}

/**
 * Grouping session
 * tujuan : untuk mencegah tabrakan session antar website
 * 
 * Penggunaan :
 * sess('variable', 'value') : untuk memberikan session value   -> set session
 * sess('variable')          : untuk membaca session value      -> get session
 * 
 */
$group = 'membership';

function sess($key = NULL, $value = NULL)
{
    _sess_start();
    global $group;

    if (isset($key) && isset($value))
    {
        return $_SESSION[$group][$key] = $value;
    }

    if (isset($_SESSION[$group]))
    {
        if (isset($_SESSION[$group][$key]))
        {
            if (is_array($_SESSION[$group][$key]))
            {
                return _parse_to_object($_SESSION[$group][$key]);
            }

            return $_SESSION[$group][$key];
        }

        if (is_null($key))
        {
            return _parse_to_object($_SESSION[$group]);
        }
    }

    return NULL;
}

// Session destroy : untuk destroy session value dalam 1 group
// Penggunaan      : sess_destroy()
function sess_destroy()
{
    _sess_start();
    global $group;
    unset($_SESSION[$group]);
}

/**
 * Session message : untuk membuat flash session, session sekali pakai
 * 
 * Penggunaan :
 * sess_msg('variable', 'message') : untuk memberikan session message value
 * sess_msg('variable')            : untuk membaca session message value
 * 
 */
function sess_msg($key = NULL, $value = NULL)
{
    _sess_start();
    global $group;

    if (isset($key) && isset($value))
    {
        return $_SESSION[$group][$key] = $value;
    }

    if (isset($_SESSION[$group]) && isset($key))
    {
        $msg = $_SESSION[$group][$key];
        unset($_SESSION[$group][$key]);

        return $msg;
    }

    return NULL;
}

// Has message : untuk mengecek session message ada atau tidak
// Penggunaan  : has_msg('key_message')
function has_msg($key = NULL)
{
    _sess_start();
    global $group;

    if (isset($_SESSION[$group]) && isset($key)) 
    {
        return isset($_SESSION[$group][$key]) ? TRUE : FALSE;
    }

    return FALSE;
}

// redirect page    : mempersingkat syntax header
// Penggunaan       : redirect('namafile.php') atau redirect('namafile.php', 307)
function redirect($url, $status_code = 303)
{
    header('location: ' . $url, TRUE, $status_code);
    die();
}

// validasi input post  : memberikan nilai default jika variable null
// fungsi native PHP    : $_POST
// Penggunaan           : input_post('var') atau input_post('var', 0)
function input_post($var = NULL, $return = NULL)
{
    if (isset($var))
    {
        return isset($_POST[$var]) ? addslashes(trim($_POST[$var])) : trim($return);
    }

    return trim($return);
}

// validasi input get   : memberikan nilai default jika variable null
// fungsi native PHP    : $_GET
// Penggunaan           : input_get('var') atau input_get('var', 0)
function input_get($var = NULL, $return = NULL)
{
    if (isset($var))
    {
        return isset($_GET[$var]) ? $_GET[$var] : trim($return);
    }

    return trim($return);
}

// JSON response    : mengonversi data ke tipe data JSON -> biasanya untuk AJAX
// Penggunaan       : json_response($data_array) atau json_response($data_array, 201)
function json_response($data = NULL, $status = 200)
{
    $message = NULL;
    switch ($status) {
        case 200:
            $message = 'OK';
            break;

        case 201:
            $message = 'Created';
            break;
        
        default:
            $status  = 500;
            $message = 'Internal Server Error';
            break;
    }

    $return = array(
        'status'    => $status,
        'message'   => $message,
        'data'      => $data,
    );

    header('Content-Type: application/json');
    echo json_encode($return);
    exit();
}

// NULL value   : memberikan nilai default jika variable null
// Penggunaan   : null_value($variable) atau null_value($variable, 0)
function null_value($value, $return = NULL)
{
    return is_null($value) ? $return : $value;
}

/**
 * Private function
 * Untuk membantu fungsi-fungsi public core helper
 * 
 * Parsing array data to object std class
 */
function _parse_to_object($arr = array())
{
    $object = new stdClass();

    foreach ($arr as $key => $value)
    {
        if (is_array($value))
        {
            $object->$key = _parse_to_object($value);
        }
        else
        {
            $object->$key = $value;
        }
    }

    return $object;
}

function phone_number($nohp)
{
    if(substr($nohp, 0, 1) == '0'){
        $nohp = '62' . substr($nohp, 1);
    }
    else if(substr($nohp, 0, 1) == '8'){
        $nohp = '62' . $nohp;
    }
    else if(substr($nohp, 0, 3) == '+62' || substr($nohp, 0, 3) == '+82'){
        $nohp = '62' . substr($nohp, 3);
    }
    else if(substr($nohp, 0, 2) == '+0'){
        $nohp = '62' . substr($nohp, 2);
    }

    return $nohp;
}

function send_whatsapp($data){
    $waktu  = "07:30 WIB - 13:00 WIB";
    $link   = "http://localhost/meeting_registration/information?q=" . base64_encode("id=" . $data['id']);

    $curl = curl_init();
    
    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://wa01.ocatelkom.co.id/api/v2/push/message',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'{ 
        "phone_number": "' . phone_number($data['nohp']) . '", 
        "message": { 
            "type": "template", 
            "template": { 
                "template_code_id": "f8836be1_7bb9_4abe_891c_ff2391904ffb:testing_reminder_block_meeting", 
                "payload": [ 
                    { 
                        "position": "body", 
                        "parameters": [
                            {
                                "type": "text",
                                "text": "' . $data['nama'] . '"
                            },
                            {
                                "type": "text",
                                "text": "' . $waktu . '"
                            },
                            {
                                "type": "text",
                                "text": "' . $link . '"
                            }
                        ] 
                    } 
                ] 
            } 
        } 
    }',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwiYXBwbGljYXRpb24iOiI2MTVlODAyMDVhYmIxMTAwMjAxOTQ1YWIiLCJpYXQiOjE1MTYyMzkwMjJ9.RGJY2DAaP0y3SJbTPBcWz4nEDHUeqPfUoD404i133n7m3-odsCeexO5p4RNUsJ26xrLV-iRAcoU3aljjLUDTMYoqD495KdW8XMykbImEGrFe5HJT1jmtIsKCTJZlQFNAcaualm18Uny0h7S_mHnjDc8s8L8Wj2CGxcLflOoEXnRZ_IvEEJlK3VpQ-kGq82vXKOBYGzQ8trV1jKe6xj3Eh5QshejCgWr2_SFxNW_8cEpAaSVXg4h4T-VUqcSCjmf4oBfSYgrz7a95nQ3K5BZiqffgWmVqvNK2vydvfB3eNQjK1b0fyj30ohn_uVzGkSByP130xfNx4RoG7lsj6bGNOg'
    ),
    ));

    $response = curl_exec($curl);
    
    curl_close($curl);

    return $response;
}

// Session start : untuk memulai session jika session belum dimulai
function _sess_start()
{
    if (session_status() == 1) session_start();
}
?>