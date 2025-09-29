# Introdução

Documentação oficial da API REST responsável por autenticação, gestão de produtos e rastreamento de auditoria.

<aside>
    <strong>URL base</strong>: <code>http://localhost:8000</code>
</aside>

<p><strong>Documentação interativa</strong> com suporte a exemplos executáveis via <em>Testar requisição</em>.</p>
<h3>Fluxos principais</h3>
<ul>
    <li><strong>Autenticação</strong>: gere tokens seguros utilizando o endpoint de login.</li>
    <li><strong>Produtos</strong>: cadastre, liste e mantenha o catálogo com filtros avançados.</li>
    <li><strong>Logs de auditoria</strong>: acompanhe as alterações realizadas nos modelos monitorados.</li>
</ul>
<h3>Boas práticas</h3>
<ol>
    <li>Envie sempre o cabeçalho <code>Accept: application/json</code>.</li>
    <li>Utilize um token obtido em <code>POST /api/v1/auth/login</code> no cabeçalho <code>Authorization</code>.</li>
    <li>Aplique filtros e paginação para otimizar as consultas.</li>
</ol>
