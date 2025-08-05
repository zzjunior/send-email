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
        if (!isset($uploadedFiles['file'])) {
            $response->getBody()->write(json_encode(['error' => 'Arquivo não enviado']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $file = $uploadedFiles['file'];
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'Erro no upload']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
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
        $emailService = new EmailService('sendgrid');
        $success = 0;
        foreach ($emails as $contato) {
            if ($emailService->send($contato['email'], $contato['name'], 'Assunto Exemplo', 'Olá, '.$contato['name'].'!')) {
                $success++;
            }
        }
        $response->getBody()->write(json_encode([
            'enviados' => $success,
            'total' => count($emails)
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
