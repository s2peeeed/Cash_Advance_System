<?php
// Create PHPMailer directory if it doesn't exist
if (!file_exists('includes/PHPMailer')) {
    mkdir('includes/PHPMailer', 0777, true);
    mkdir('includes/PHPMailer/src', 0777, true);
}

// PHPMailer files content
$files = [
    'src/Exception.php' => '<?php
namespace PHPMailer\PHPMailer;

class Exception extends \Exception
{
    public function errorMessage()
    {
        return \'<strong>Mailer Error: </strong>\' . $this->getMessage();
    }
}',
    'src/PHPMailer.php' => '<?php
namespace PHPMailer\PHPMailer;

class PHPMailer
{
    public $Host = "";
    public $Port = 25;
    public $SMTPAuth = false;
    public $Username = "";
    public $Password = "";
    public $SMTPSecure = "";
    public $From = "";
    public $FromName = "";
    public $Subject = "";
    public $Body = "";
    public $AltBody = "";
    public $isHTML = false;
    private $to = [];
    private $attachments = [];

    public function isSMTP()
    {
        return true;
    }

    public function addAddress($address)
    {
        $this->to[] = $address;
    }

    public function clearAddresses()
    {
        $this->to = [];
    }

    public function setFrom($address, $name = "")
    {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function send()
    {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->FromName . " <" . $this->From . ">" . "\r\n";

        foreach ($this->to as $to) {
            mail($to, $this->Subject, $this->Body, $headers);
        }
        return true;
    }
}',
    'src/SMTP.php' => '<?php
namespace PHPMailer\PHPMailer;

class SMTP
{
    const CRLF = "\r\n";
    const MAX_LINE_LENGTH = 998;
}'
];

// Write files
foreach ($files as $file => $content) {
    file_put_contents('includes/PHPMailer/' . $file, $content);
}

echo "PHPMailer has been set up successfully!";
?> 