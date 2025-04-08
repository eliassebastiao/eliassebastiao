
# KEIMADURA - Sistema de Gestão

## Configuração para XAMPP

### Requisitos
- XAMPP instalado (Apache e MySQL)
- PHP 7.4 ou superior
- Navegador web moderno

### Instruções de Instalação

1. **Configurar a pasta do projeto:**
   - Copie todos os arquivos do projeto para a pasta `htdocs` do XAMPP
   - Exemplo: `C:\xampp\htdocs\keimadura`

2. **Configurar o banco de dados:**
   - Inicie o XAMPP Control Panel
   - Inicie os serviços Apache e MySQL
   - Acesse o phpMyAdmin: http://localhost/phpmyadmin
   - Crie um novo banco de dados chamado `keimadura_db`

3. **Instalação do sistema:**
   - Acesse o sistema pelo navegador: http://localhost/keimadura
   - O sistema redirecionará automaticamente para a página de instalação
   - Siga as instruções na tela para criar as tabelas e dados iniciais

4. **Login no sistema:**
   - Após a instalação, use os seguintes dados para login:
     - Usuário: `Keimadura`
     - Senha: `keimaduraadmin`
   - Alternativamente, use estes usuários:
     - Usuário: `keimaduraserviço1` / Senha: `serviço1`
     - Usuário: `keimaduraserviço2` / Senha: `serviço2`

### Estrutura de Pastas

- `Estilos/` - Arquivos CSS
- `Js/` - Scripts JavaScript
- `Paginas/` - Componentes HTML
- `attached_assets/` - Imagens e outros recursos
- Arquivos PHP principais na raiz

### Solução de Problemas

Se encontrar problemas:

1. Verifique se o XAMPP está em execução (Apache e MySQL)
2. Verifique se os dados de conexão em `config.php` estão corretos
3. Execute o diagnóstico: http://localhost/keimadura/test_connection.php

## Funcionalidades

- Gestão de Vendas
- Catálogo de Produtos
- Controle de Estoque
- Gestão Financeira
- Relatórios e Informações
- Configurações do Sistema

## Contato e Suporte

Para dúvidas ou suporte, entre em contato:
- Email: suporte@keimadura.com
