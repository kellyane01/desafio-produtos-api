# Autenticando requisições

Para autenticar as requisições, inclua o cabeçalho **`Authorization`** com o valor **`"Bearer {ACCESS_TOKEN}"`**.

Todos os endpoints autenticados exibem o selo `requer autenticação` na documentação abaixo.

<p>Sempre inclua <code>Authorization: Bearer {token}</code> e substitua <code>{token}</code> pelo valor recebido após o login.</p>
<p>Tokens podem ser revogados a qualquer momento via <code>POST /api/v1/auth/logout</code>.</p>
