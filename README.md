# recarga_beneficios
Sistema de controle de beneficios de colaboradores

## API de benefícios (consulta por CPF)
- Configure o token na `.env`: `API_ACCESS_TOKEN=suatokenforte` (e mantenha-o fora de commits).
- Endpoint: `POST /api/employees/benefits` com `Authorization: Bearer {API_ACCESS_TOKEN}` ou header `X-API-TOKEN`.
- Corpo JSON: `{"cpf": "12345678909"}` (apenas dígitos ou com máscara; o backend normaliza).
- Resposta: dados básicos do funcionário, último fechamento de jornadas (`workdays`) e benefícios com valores mensais mais recentes.
- Exemplo rápido:
```bash
curl -X POST http://localhost/api/employees/benefits \
  -H "Authorization: Bearer $API_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"cpf":"123.456.789-09"}'
```
