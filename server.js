// Servidor HTTP simples para o sistema KEIMADURA
const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 3000;

console.log('NOTA: Este servidor web simples é alternativo ao XAMPP.');
console.log('Para usar com XAMPP, coloque todos os arquivos na pasta htdocs do XAMPP.');

const server = http.createServer((req, res) => {
    console.log(`Requisição: ${req.url}`);
    
    // Definir o caminho do arquivo
    let filePath = '.' + req.url;
    if (filePath === './') {
        filePath = './index.html';
    }
    
    // Obter a extensão do arquivo
    const extname = String(path.extname(filePath)).toLowerCase();
    
    // Tipos MIME
    const mimeTypes = {
        '.html': 'text/html',
        '.js': 'text/javascript',
        '.css': 'text/css',
        '.json': 'application/json',
        '.png': 'image/png',
        '.jpg': 'image/jpeg',
        '.jpeg': 'image/jpeg',
        '.gif': 'image/gif',
        '.svg': 'image/svg+xml',
        '.ico': 'image/x-icon',
        '.woff': 'application/font-woff',
        '.woff2': 'application/font-woff2',
        '.ttf': 'application/font-ttf',
        '.eot': 'application/vnd.ms-fontobject',
        '.otf': 'application/font-otf'
    };
    
    // Tipo de conteúdo
    const contentType = mimeTypes[extname] || 'application/octet-stream';
    
    // Ler o arquivo
    fs.readFile(filePath, (error, content) => {
        if (error) {
            if (error.code === 'ENOENT') {
                // Página não encontrada - redirecionar para index.html
                fs.readFile('./index.html', (error, content) => {
                    if (error) {
                        res.writeHead(500);
                        res.end('Erro interno do servidor: ' + error.code);
                    } else {
                        res.writeHead(200, { 'Content-Type': 'text/html' });
                        res.end(content, 'utf-8');
                    }
                });
            } else {
                // Outros erros de servidor
                res.writeHead(500);
                res.end('Erro interno do servidor: ' + error.code);
            }
        } else {
            // Sucesso
            res.writeHead(200, { 'Content-Type': contentType });
            res.end(content, 'utf-8');
        }
    });
});

server.listen(PORT, '0.0.0.0', () => {
    console.log('Servidor KEIMADURA rodando em http://0.0.0.0:' + PORT + '/');
    console.log('Para acessar o sistema, abra o URL acima em seu navegador.');
});