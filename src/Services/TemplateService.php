<?php
namespace App\Services;

class TemplateService
{
    private $templatesPath;
    
    public function __construct()
    {
        $this->templatesPath = __DIR__ . '/../../templates/';
    }
    
    /**
     * Renderiza um template com as variáveis fornecidas
     */
    public function render(string $templateName, array $variables = []): string
    {
        $templatePath = $this->templatesPath . $templateName . '.html';
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template '{$templateName}' não encontrado");
        }
        
        $content = file_get_contents($templatePath);
        
        // Substitui variáveis simples {{variable}}
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // Processa condicionais simples {{#if variable}}...{{/if}}
        $content = $this->processConditionals($content, $variables);
        
        // Remove variáveis não substituídas
        $content = preg_replace('/\{\{[^}]+\}\}/', '', $content);
        
        return $content;
    }
    
    /**
     * Lista todos os templates disponíveis
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];
        $files = glob($this->templatesPath . '*.html');
        
        foreach ($files as $file) {
            $name = basename($file, '.html');
            $templates[$name] = [
                'name' => $name,
                'file' => $file,
                'title' => ucfirst(str_replace(['_', '-'], ' ', $name))
            ];
        }
        
        return $templates;
    }
    
    /**
     * Processa condicionais simples no template
     */
    private function processConditionals(string $content, array $variables): string
    {
        // Padrão para encontrar {{#if variable}}...{{/if}}
        $pattern = '/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/s';
        
        return preg_replace_callback($pattern, function($matches) use ($variables) {
            $variable = trim($matches[1]);
            $conditionalContent = $matches[2];
            
            // Se a variável existe e não está vazia, mantém o conteúdo
            if (isset($variables[$variable]) && !empty($variables[$variable])) {
                return $conditionalContent;
            }
            
            // Caso contrário, remove o conteúdo
            return '';
        }, $content);
    }
    
    /**
     * Valida se todas as variáveis obrigatórias estão presentes
     */
    public function validateVariables(string $templateName, array $variables): array
    {
        $templatePath = $this->templatesPath . $templateName . '.html';
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template '{$templateName}' não encontrado");
        }
        
        $content = file_get_contents($templatePath);
        
        // Encontra todas as variáveis no template
        preg_match_all('/\{\{([^#\/][^}]*)\}\}/', $content, $matches);
        $templateVariables = array_unique($matches[1]);
        
        $missing = [];
        foreach ($templateVariables as $var) {
            $var = trim($var);
            if (!isset($variables[$var]) || empty($variables[$var])) {
                $missing[] = $var;
            }
        }
        
        return $missing;
    }
}
