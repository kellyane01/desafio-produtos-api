# Decisões técnicas e limites

Este documento explica a organização e os compromissos da API de produtos descrita no repositório. Não apresenta resultados de benchmark ou garantias de operação em produção.

## Separação de responsabilidades

Controllers recebem e coordenam as requisições HTTP; serviços concentram o fluxo de aplicação; repositórios organizam o acesso aos dados. Requests e Resources cuidam dos contratos de entrada e saída. Observers e Jobs apoiam a reação às alterações e o processamento em segundo plano.

Essa divisão ajuda a localizar regras e testar partes isoladamente. Em contrapartida, acrescenta indireção: uma mudança pode atravessar mais de uma camada. Contratos e abstrações devem ter uma responsabilidade concreta, evitando duplicar o ORM sem necessidade.

## PostgreSQL e Elasticsearch

O PostgreSQL mantém a persistência relacional. O Elasticsearch atende à busca full-text e aos recursos de relevância descritos no projeto. A documentação existente prevê indexação em segundo plano e fallback para consultas relacionais quando o mecanismo de busca está indisponível.

A atualização assíncrona do índice implica consistência eventual: um produto recém-alterado pode levar algum tempo para aparecer na busca. É necessário observar falhas de indexação, reprocessar jobs e validar a reindexação em cenários reais. O fallback relacional mantém uma alternativa de consulta, mas não garante resultados com a mesma ordenação ou relevância.

## Filas, logs e observabilidade

Redis e Horizon apoiam o processamento assíncrono e o acompanhamento das filas. Tirar operações do caminho síncrono da requisição reduz o trabalho imediato, mas introduz dependência da operação dos workers e de mecanismos de recuperação de falhas.

Logs de aplicação e logs de auditoria têm finalidades diferentes. Ao evoluir o projeto, convém explicitar retenção, campos sensíveis, identificação da operação e regras de acesso aos registros. O dashboard Horizon deve respeitar o ambiente e a autorização configurada.

## Estratégia de testes

O `phpunit.xml` inspecionado define SQLite em memória, fila síncrona, cache em array e ambiente `testing`. Essa configuração favorece testes rápidos e repetíveis. Ela não exercita, por si só, o comportamento real de PostgreSQL, múltiplos workers Redis ou Elasticsearch.

Uma etapa de integração deve validar diferenças de SQL, serialização de jobs, falhas e retomada de workers, sincronização do índice e comportamento do fallback. O workflow de Pint e PHPUnit não deve ser descrito como validação completa da infraestrutura.

## Desempenho

A documentação inclui seed configurável para volumes maiores. Esse recurso permite preparar um conjunto de dados, mas não constitui uma medição de desempenho.

Para publicar um benchmark reproduzível, registrar: quantidade de registros, distribuição dos dados, recursos do ambiente, versões dos serviços, requisições utilizadas, concorrência, cache frio/quente, latências e taxa de erros. Separar medições reais de metas futuras.
