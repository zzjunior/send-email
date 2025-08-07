<?php
namespace App\Services;

use SendGrid;

class EmailService
{
    private $provider;
    private $templateService;

    public function __construct($provider)
    {
        $this->provider = $provider;
        $this->templateService = new TemplateService();
    }

    /**
     * Envia e-mail simples com conteúdo texto
     */
    public function send($to, $name, $subject, $content)
    {
        if ($this->provider === 'sendgrid') {
            // Obtém e valida configurações do remetente
            $fromEmail = getenv('MAIL_FROM');
            $fromName = getenv('MAIL_FROM_NAME');
            
            // Remove aspas se existirem
            $fromEmail = trim($fromEmail, '"\'');
            $fromName = trim($fromName, '"\'');
            
            // Valores padrão caso as variáveis estejam vazias
            if (empty($fromEmail)) {
                $fromEmail = 'noreply@example.com';
            }
            if (empty($fromName)) {
                $fromName = 'Sistema';
            }
            
            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($fromEmail, $fromName);
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
        return false;
    }
    
    /**
     * Envia e-mail usando template HTML
     */
    public function sendWithTemplate($to, $name, $subject, $templateName, $templateVariables = [])
    {
        try {
            // Adiciona variáveis padrão
            $defaultVariables = [
                'name' => $name,
                'subject' => $subject,
                'company_name' => getenv('COMPANY_NAME') ?: 'Sua Empresa',
                'company_address' => getenv('COMPANY_ADDRESS') ?: '',
                'company_phone' => getenv('COMPANY_PHONE') ?: '',
                'company_email' => getenv('COMPANY_EMAIL') ?: getenv('MAIL_FROM'),
                'sender_name' => getenv('SENDER_NAME') ?: getenv('MAIL_FROM_NAME'),
                'unsubscribe_url' => getenv('UNSUBSCRIBE_URL') ?: '#'
            ];
            
            // Mescla variáveis padrão com as fornecidas
            $variables = array_merge($defaultVariables, $templateVariables);
            
            // Renderiza o template
            $htmlContent = $this->templateService->render($templateName, $variables);
            
            if ($this->provider === 'sendgrid') {
                // Obtém e valida configurações do remetente
                $fromEmail = getenv('MAIL_FROM');
                $fromName = getenv('MAIL_FROM_NAME');
                
                // Remove aspas se existirem
                $fromEmail = trim($fromEmail, '"\'');
                $fromName = trim($fromName, '"\'');
                
                // Valores padrão caso as variáveis estejam vazias
                if (empty($fromEmail)) {
                    $fromEmail = 'noreply@example.com';
                }
                if (empty($fromName)) {
                    $fromName = 'Sistema';
                }
                
                // Validação do e-mail
                if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("E-mail do remetente inválido: {$fromEmail}");
                }
                
                $email = new \SendGrid\Mail\Mail();
                $email->setFrom($fromEmail, $fromName);
                $email->setSubject($subject);
                $email->addTo($to, $name);
                
                // Adiciona conteúdo HTML
                $email->addContent('text/html', $htmlContent);
                
                // Gera versão texto simples (opcional)
                $textContent = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlContent));
                $email->addContent('text/plain', $textContent);
                
                $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
                $response = $sendgrid->send($email);
                return $response->statusCode() < 300;
            }
        } catch (\Exception $e) {
            error_log("Erro ao enviar e-mail com template: " . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Obtém lista de templates disponíveis
     */
    public function getAvailableTemplates()
    {
        return $this->templateService->getAvailableTemplates();
    }
    
    /**
     * Valida variáveis do template
     */
    public function validateTemplateVariables($templateName, $variables)
    {
        return $this->templateService->validateVariables($templateName, $variables);
    }
}
