<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\EmailService;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmailController
{
    public function send(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();
        $parsedBody = $request->getParsedBody();
        
        // Verifica se arquivo foi enviado
        if (!isset($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode(['error' => 'Arquivo não enviado']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $file = $uploadedFiles['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'Erro no upload']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Obtém parâmetros do template
        $templateName = $parsedBody['template'] ?? 'promotional';
        $subject = $parsedBody['subject'] ?? 'Assunto do E-mail';
        $message = $parsedBody['message'] ?? 'Olá! Este é um e-mail de teste.';
        
        // Variáveis personalizadas do template
        $templateVariables = [
            'message' => $message,
            'subject' => $subject
        ];
        
        // Adiciona variáveis extras se fornecidas
        if (isset($parsedBody['template_vars']) && is_array($parsedBody['template_vars'])) {
            $templateVariables = array_merge($templateVariables, $parsedBody['template_vars']);
        }
        
        // Processa o arquivo de contatos
        $filename = $file->getClientFilename();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $emails = [];
        
        if ($ext === 'csv') {
            $csv = fopen($file->getStream()->getMetadata('uri'), 'r');
            while (($data = fgetcsv($csv, 1000, ",")) !== false) {
                if (isset($data[0], $data[1])) {
                    $emails[] = ['email' => $data[0], 'name' => $data[1]];
                }
            }
            fclose($csv);
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            $spreadsheet = IOFactory::load($file->getStream()->getMetadata('uri'));
            $sheet = $spreadsheet->getActiveSheet();
            foreach ($sheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                if (isset($rowData[0], $rowData[1])) {
                    $emails[] = ['email' => $rowData[0], 'name' => $rowData[1]];
                }
            }
        } else {
            $response->getBody()->write(json_encode(['error' => 'Formato de arquivo não suportado']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Inicializa o serviço de e-mail
        $emailService = new EmailService('sendgrid');
        $success = 0;
        $errors = [];
        
        // Envia e-mails usando template
        foreach ($emails as $contato) {
            try {
                if ($emailService->sendWithTemplate(
                    $contato['email'], 
                    $contato['name'], 
                    $subject, 
                    $templateName, 
                    $templateVariables
                )) {
                    $success++;
                } else {
                    $errors[] = "Falha ao enviar para: " . $contato['email'];
                }
            } catch (\Exception $e) {
                $errors[] = "Erro para " . $contato['email'] . ": " . $e->getMessage();
            }
        }
        
        $response->getBody()->write(json_encode([
            'enviados' => $success,
            'total' => count($emails),
            'template_usado' => $templateName,
            'erros' => $errors
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Lista templates disponíveis
     */
    public function listTemplates(Request $request, Response $response): Response
    {
        $emailService = new EmailService('sendgrid');
        $templates = $emailService->getAvailableTemplates();
        
        $response->getBody()->write(json_encode([
            'templates' => $templates
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Gera preview de um template específico
     */
    public function getTemplatePreview(Request $request, Response $response, $args): Response
    {
        $templateName = $args['name'];
        $emailService = new EmailService('sendgrid');
        
        try {
            // Dados de exemplo para preview
            $sampleData = [
                'name' => 'João Silva',
                'subject' => 'Exemplo de Assunto',
                'message' => 'Esta é uma mensagem de exemplo para visualização do template.',
                'company_name' => getenv('COMPANY_NAME') ?: 'Sua Empresa',
                'company_address' => getenv('COMPANY_ADDRESS') ?: 'Endereço da Empresa',
                'company_phone' => getenv('COMPANY_PHONE') ?: '(11) 99999-9999',
                'company_email' => getenv('COMPANY_EMAIL') ?: 'contato@empresa.com',
                'sender_name' => getenv('SENDER_NAME') ?: 'Equipe',
                
                // Variáveis específicas por template
                'button_text' => 'Ver Oferta',
                'button_url' => '#',
                'highlight_message' => 'Destaque importante!',
                'additional_info' => 'Informações adicionais sobre o comunicado.',
                'tagline' => 'Conectando você ao futuro',
                'call_to_action' => 'Saiba Mais',
                'cta_url' => '#',
                'social_facebook' => '#',
                'social_instagram' => '#',
                'social_linkedin' => '#'
            ];
            
            // Gera o HTML do template
            $templateService = new \App\Services\TemplateService();
            $htmlContent = $templateService->render($templateName, $sampleData);
            
            // Retorna informações do template + preview
            $templateInfo = [
                'name' => ucfirst(str_replace(['_', '-'], ' ', $templateName)),
                'description' => $this->getTemplateDescription($templateName),
                'variables' => $this->getTemplateVariables($templateName),
                'preview' => $htmlContent
            ];
            
            $response->getBody()->write(json_encode($templateInfo));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Template não encontrado: ' . $e->getMessage()
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * Retorna descrição do template
     */
    private function getTemplateDescription($templateName): string
    {
        $descriptions = [
            'promotional' => 'Template moderno com cabeçalho colorido, ideal para campanhas promocionais e ofertas especiais. Design atrativo com botão de call-to-action.',
            'newsletter' => 'Template profissional com design limpo, perfeito para newsletters e comunicados informativos. Inclui área de destaque e rodapé corporativo.',
            'modern' => 'Design elegante com gradiente e visual contemporâneo. Inclui links para redes sociais e botão de call-to-action estilizado.',
            'notificacaoteiacrm' => 'Template institucional específico do TEIA CRM com logo oficial, formatação profissional e caixa de destaque para informações importantes.'
        ];
        
        return $descriptions[$templateName] ?? 'Template personalizado';
    }
    
    /**
     * Retorna variáveis disponíveis do template
     */
    private function getTemplateVariables($templateName): array
    {
        $variables = [
            'promotional' => ['message', 'button_text', 'button_url', 'company_name'],
            'newsletter' => ['message', 'highlight_message', 'additional_info', 'sender_name'],
            'modern' => ['message', 'tagline', 'call_to_action', 'cta_url', 'social_facebook', 'social_instagram'],
            'notificacaoteiacrm' => ['subject']
        ];
        
        return $variables[$templateName] ?? ['message'];
    }
}
