<?php
namespace App;

use SendGrid;

class EmailService
{
    private $provider;

    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    public function send($to, $name, $subject, $content)
    {
        // Implementação para SendGrid
        $email = new \SendGrid\Mail\Mail();
        $email->setFrom(getenv('MAIL_FROM'), getenv('MAIL_FROM_NAME'));
        $email->setSubject($subject);
        $email->addTo($to, $name);
        $email->addContent('text/plain', $content);
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $response = $sendgrid->send($email);
            return $response->statusCode() < 300;
        } catch (\Exception $e) {
            return false;
        }
    }
}
