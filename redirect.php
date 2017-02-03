
<?php 
require_once('../../../wp-load.php');
$redirect = WC()->session->get('icredit_iframe_redirect_url');
if ($redirect == '')
{
    $redirect='about:blank';
}
else
{
    $token = $_GET['Token'];
    if (strpos($redirect, '?') > 0) 
    { 
        $redirect .= '&'; 
    }
    else 
    { 
        $redirect .= '?'; 
    }
    
    $redirect .= 'Token='.$token;
}
?>

<script>
    window.top.location = "<?= $redirect; ?>";
</script>
