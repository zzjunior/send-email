# 📧 Send-Email - Sistema de E-mails em Massa

Projeto PHP com Slim Framework para envio de e-mails em massa via SendGrid, com upload de planilhas (CSV/Excel) e sistema de templates HTML responsivos.

## ✨ Funcionalidades

- 📤 Envio de e-mails em massa via SendGrid
- 📊 Upload de planilhas (CSV, XLS, XLSX) com lista de contatos
- 🎨 Sistema de templates HTML responsivos
- ⚙️ Variáveis personalizáveis nos templates
- 🔄 Arquitetura modular para fácil troca de provedor de e-mail
- 🌐 Interface web amigável

## 🚀 Como usar

### 1. Instalação
```bash
composer install
```

### 2. Configuração
Copie o arquivo `.env.example` para `.env` e configure:
```bash
cp .env.example .env
```

Configure no arquivo `.env`:
```env
SENDGRID_API_KEY=sua_chave_sendgrid_aqui
MAIL_FROM=noreply@suaempresa.com
MAIL_FROM_NAME=Sua Empresa
COMPANY_NAME=Sua Empresa
# ... outras configurações
```

### 3. Executar o servidor
```bash
php -S localhost:8080 -t public
```

### 4. Usar a aplicação
1. Acesse `http://localhost:8080`
2. Escolha um template de e-mail
3. Digite o assunto e mensagem
4. Faça upload da planilha com contatos (formato: Email, Nome)
5. Configure variáveis personalizadas (opcional)
6. Clique em "Enviar E-mails"

## 🎨 Templates Disponíveis

### 1. **Promocional** (`promotional`)
- Design moderno com cabeçalho colorido
- Botão de call-to-action
- Ideal para campanhas promocionais

**Variáveis disponíveis:**
- `message` - Mensagem principal
- `button_text` - Texto do botão
- `button_url` - URL do botão
- `company_name` - Nome da empresa

### 2. **Newsletter** (`newsletter`)
- Design limpo e profissional
- Área de destaque
- Informações de contato no rodapé

**Variáveis disponíveis:**
- `message` - Mensagem principal
- `highlight_message` - Mensagem em destaque
- `additional_info` - Informações adicionais
- `sender_name` - Nome do remetente

### 3. **Moderno** (`modern`)
- Design elegante com gradiente
- Links para redes sociais
- Visual contemporâneo

**Variáveis disponíveis:**
- `message` - Mensagem principal
- `tagline` - Slogan da empresa
- `call_to_action` - Texto do botão principal
- `cta_url` - URL do botão
- `social_facebook`, `social_instagram`, `social_linkedin` - URLs das redes sociais

## 📋 Formato da Planilha

A planilha deve conter 2 colunas:
| Email | Nome |
|--------|------|
| joao@email.com | João Silva |
| maria@email.com | Maria Santos |

Formatos suportados: `.csv`, `.xls`, `.xlsx`

## 🔧 API Endpoints

### `GET /`
Interface web principal

### `POST /send-emails`
Envia e-mails em massa

**Parâmetros:**
- `file` - Arquivo da planilha (obrigatório)
- `template` - Nome do template (padrão: promotional)
- `subject` - Assunto do e-mail (obrigatório)
- `message` - Mensagem principal (obrigatório)
- `template_vars` - JSON com variáveis personalizadas (opcional)

### `GET /templates`
Lista templates disponíveis

**Resposta:**
```json
{
  "templates": {
    "promotional": {
      "name": "promotional",
      "title": "Promocional"
    }
  }
}
```

## 🏗️ Tecnologias

- **PHP 8.3+**
- **Slim Framework 4** - Framework web
- **SendGrid PHP SDK** - Envio de e-mails
- **PhpSpreadsheet** - Leitura de planilhas Excel
- **League CSV** - Processamento de arquivos CSV
- **Twig** - Sistema de templates

## 📁 Estrutura do Projeto

```
├── public/
│   └── index.php              # Ponto de entrada
├── src/
│   ├── Controllers/
│   │   ├── EmailController.php # Controle de envio
│   │   └── HomeController.php  # Página inicial
│   └── Services/
│       ├── EmailService.php    # Serviço de e-mail
│       └── TemplateService.php # Gerenciamento de templates
├── templates/
│   ├── promotional.html        # Template promocional
│   ├── newsletter.html         # Template newsletter
│   └── modern.html            # Template moderno
├── views/
│   └── home.twig              # Interface web
├── routes/
│   └── web.php                # Definição de rotas
└── composer.json              # Dependências
```

## 🔄 Extensibilidade

### Adicionar Novo Provedor de E-mail
1. Modificar `EmailService.php`
2. Adicionar lógica para o novo provedor
3. Configurar credenciais no `.env`

### Criar Novo Template
1. Criar arquivo HTML em `/templates/`
2. Usar sintaxe: `{{variavel}}` para substituições
3. Usar `{{#if variavel}}...{{/if}}` para condicionais

### Exemplo de template personalizado:
```html
<!DOCTYPE html>
<html>
<head>
    <title>{{subject}}</title>
</head>
<body>
    <h1>Olá, {{name}}!</h1>
    <p>{{message}}</p>
    
    {{#if special_offer}}
    <div class="offer">
        <h2>Oferta Especial!</h2>
        <p>{{special_offer}}</p>
    </div>
    {{/if}}
    
    <footer>{{company_name}}</footer>
</body>
</html>
```

## 🛡️ Segurança

- Validação de tipos de arquivo
- Sanitização de dados de entrada
- Tratamento de erros
- Logs de envio

## 📝 Observações

- Não armazena dados permanentemente
- Processa planilhas em memória
- Adequado para listas de até 10.000 contatos
- Respeita limites de API do SendGrid

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Abra um Pull Request
