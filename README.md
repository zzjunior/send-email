# Send-email (PHP + Slim)

Projeto para envio de e-mails em massa via SendGrid, com upload de planilhas (CSV/Excel).

## Como usar

1. Instale as dependências:
   ```bash
   composer install
   ```
2. Configure sua chave SendGrid em um arquivo `.env` (a ser criado).
3. Rode o servidor:
   ```bash
   php -S localhost:8080 index.php
   ```
4. Faça um POST para `/send-emails` com o arquivo de contatos.

## Tecnologias
- PHP
- Slim Framework
- SendGrid
- League CSV / PhpSpreadsheet

## Observações
- Não armazena dados, apenas faz o disparo.
- Estruturado para fácil troca de serviço de e-mail.
