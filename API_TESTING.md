# Guia de Testes — Health Board API

Base URL: `https://health-board-api.test/api`

Todos os endpoints (exceto `/webhooks/github`) exigem autenticação via Bearer token.

---

## Configuração

### Variáveis de ambiente (copie e cole no terminal antes de testar)

**Git Bash / Linux / macOS:**
```bash
BASE="https://health-board-api.test/api"
TOKEN="1|tJip3m4VnJfkBmKNP6kmoeIy3NEk4XM7dluAfUcqa6b92bfb"
```

**PowerShell:**
```powershell
$BASE = "https://health-board-api.test/api"
$TOKEN = "1|tJip3m4VnJfkBmKNP6kmoeIy3NEk4XM7dluAfUcqa6b92bfb"
$H = @{Authorization="Bearer $TOKEN"; Accept="application/json"; "Content-Type"="application/json"}
```

> **Nota Windows:** O certificado `.test` é auto-assinado pelo Herd. No curl, adicione `-k` para ignorar a validação SSL. No PowerShell, veja a seção de configuração no final deste documento.

---

## Repositories

### Listar todos
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/repositories | jq
```

### Criar repositório
```bash
curl -sk -X POST $BASE/repositories \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "api-gateway",
    "full_name": "seu-usuario/api-gateway",
    "github_url": "https://github.com/seu-usuario/api-gateway",
    "access_token": "ghp_seu_token_aqui"
  }' | jq
```

**Resposta esperada (201):**
```json
{
  "id": 1,
  "name": "api-gateway",
  "full_name": "seu-usuario/api-gateway",
  "github_url": "https://github.com/seu-usuario/api-gateway",
  "created_at": "2026-05-06T23:00:00.000000Z",
  "updated_at": "2026-05-06T23:00:00.000000Z"
}
```

### Detalhar repositório (com pipelines, releases e incidentes)
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/repositories/1 | jq
```

### Atualizar repositório
```bash
curl -sk -X PUT $BASE/repositories/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name": "api-gateway-v2"}' | jq
```

### Remover repositório
```bash
curl -sk -X DELETE $BASE/repositories/1 \
  -H "Authorization: Bearer $TOKEN"
# Resposta: 204 No Content
```

---

## Pipelines

### Listar todas as execuções
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/pipelines | jq
```

### Listar pipelines de um repositório
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/repositories/1/pipelines | jq
```

### Registrar execução manualmente
```bash
curl -sk -X POST $BASE/pipelines \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "repository_id": 1,
    "workflow_name": "CI Pipeline",
    "status": "success",
    "branch": "main",
    "duration": 95,
    "run_at": "2026-05-06 20:00:00"
  }' | jq
```

Valores válidos para `status`: `success`, `failure`, `cancelled`, `in_progress`

### Registrar execução com falha
```bash
curl -sk -X POST $BASE/pipelines \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "repository_id": 1,
    "workflow_name": "CI Pipeline",
    "status": "failure",
    "branch": "feature/nova-rota",
    "duration": 32,
    "run_at": "2026-05-06 21:00:00"
  }' | jq
```

### Detalhar pipeline
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/pipelines/1 | jq
```

### Remover pipeline
```bash
curl -sk -X DELETE $BASE/pipelines/1 \
  -H "Authorization: Bearer $TOKEN"
```

---

## Releases

### Listar releases
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/releases | jq
```

### Listar releases de um repositório
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/repositories/1/releases | jq
```

### Registrar release
```bash
curl -sk -X POST $BASE/releases \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "repository_id": 1,
    "version": "v1.2.0",
    "environment": "production",
    "deployed_at": "2026-05-06 22:00:00",
    "changelog": "- Adicao de autenticacao JWT\n- Correcao de bug no endpoint de usuarios"
  }' | jq
```

Valores válidos para `environment`: `dev`, `staging`, `production`

### Remover release
```bash
curl -sk -X DELETE $BASE/releases/1 \
  -H "Authorization: Bearer $TOKEN"
```

---

## Incidents

### Listar todos os incidentes
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/incidents | jq
```

### Listar apenas incidentes abertos
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/incidents/open | jq
```

### Abrir novo incidente
```bash
curl -sk -X POST $BASE/incidents \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "repository_id": 1,
    "title": "Latencia alta no endpoint /users",
    "description": "P95 acima de 2s desde as 18h. Possivel causa: query sem indice.",
    "severity": "high",
    "status": "open",
    "opened_at": "2026-05-06 18:00:00"
  }' | jq
```

Valores válidos para `severity`: `low`, `medium`, `high`, `critical`  
Valores válidos para `status`: `open`, `investigating`, `resolved`

### Atualizar status do incidente
```bash
# Mover para investigando
curl -sk -X PUT $BASE/incidents/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status": "investigating"}' | jq

# Resolver (resolved_at é preenchido automaticamente)
curl -sk -X PUT $BASE/incidents/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"status": "resolved"}' | jq
```

### Remover incidente
```bash
curl -sk -X DELETE $BASE/incidents/1 \
  -H "Authorization: Bearer $TOKEN"
```

---

## Health Score

Calcula a saúde de um repositório com base em 3 critérios:

| Critério | Pontos |
|----------|--------|
| Taxa de sucesso do CI (últimos 10 runs) | 0–50 |
| Último deploy há menos de 7 dias | 30 |
| Zero incidentes abertos | 20 |

| Score | Status |
|-------|--------|
| 80–100 | `healthy` |
| 50–79 | `degraded` |
| 0–49 | `critical` |

### Consultar score
```bash
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/health-score/1 | jq
```

**Resposta de exemplo:**
```json
{
  "repository_id": 1,
  "repository": "seu-usuario/api-gateway",
  "score": 70,
  "max_score": 100,
  "status": "degraded",
  "details": {
    "ci": {
      "score": 50,
      "max": 50,
      "success_rate": "100%",
      "runs_checked": 1
    },
    "deploy": {
      "score": 0,
      "max": 30,
      "note": "Sem releases registradas"
    },
    "incidents": {
      "score": 20,
      "max": 20,
      "open": 0
    }
  }
}
```

### Simular score crítico (passo a passo)
```bash
# 1. Registrar 3 falhas seguidas
curl -sk -X POST $BASE/pipelines -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"repository_id":1,"workflow_name":"CI","status":"failure","branch":"main","duration":10,"run_at":"2026-05-06 10:00:00"}' | jq .status
curl -sk -X POST $BASE/pipelines -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"repository_id":1,"workflow_name":"CI","status":"failure","branch":"main","duration":10,"run_at":"2026-05-06 11:00:00"}' | jq .status
curl -sk -X POST $BASE/pipelines -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"repository_id":1,"workflow_name":"CI","status":"failure","branch":"main","duration":10,"run_at":"2026-05-06 12:00:00"}' | jq .status

# 2. Abrir incidente critico
curl -sk -X POST $BASE/incidents -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"repository_id":1,"title":"Servico fora do ar","severity":"critical","status":"open","opened_at":"2026-05-06 12:00:00"}' | jq .id

# 3. Ver score degradado
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/health-score/1 | jq '{score,status}'
```

---

## Webhooks GitHub

O endpoint de webhook **não exige autenticação** (o GitHub envia diretamente).

### Simular evento `workflow_run` (pipeline)
```bash
curl -sk -X POST $BASE/../webhooks/github \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: workflow_run" \
  -d '{
    "repository": {"full_name": "seu-usuario/api-gateway"},
    "workflow_run": {
      "name": "CI Pipeline",
      "status": "completed",
      "conclusion": "success",
      "head_branch": "main",
      "run_started_at": "2026-05-06T20:00:00Z",
      "updated_at": "2026-05-06T20:01:35Z"
    }
  }' | jq
```

### Simular evento `release` (deploy)
```bash
curl -sk -X POST $BASE/../webhooks/github \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: release" \
  -d '{
    "repository": {"full_name": "seu-usuario/api-gateway"},
    "release": {
      "tag_name": "v1.3.0",
      "published_at": "2026-05-06T22:00:00Z",
      "body": "- Correcao de bug critico\n- Melhoria de performance"
    }
  }' | jq
```

### Simular repositório não monitorado
```bash
curl -sk -X POST $BASE/../webhooks/github \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: workflow_run" \
  -d '{"repository": {"full_name": "outro/repo-nao-cadastrado"}, "workflow_run": {}}' | jq
# Esperado: {"error": "Repositório não monitorado"}
```

---

## Configuração SSL no PowerShell

Rode isso uma vez por sessão do PowerShell para ignorar o certificado auto-assinado do Herd:

```powershell
add-type @"
using System.Net;
using System.Security.Cryptography.X509Certificates;
public class TrustAll : ICertificatePolicy {
    public bool CheckValidationResult(ServicePoint sp, X509Certificate cert, WebRequest req, int problem) { return true; }
}
"@
[System.Net.ServicePointManager]::CertificatePolicy = New-Object TrustAll
```

### Exemplo completo em PowerShell
```powershell
$BASE = "https://health-board-api.test/api"
$H = @{
    Authorization  = "Bearer 1|tJip3m4VnJfkBmKNP6kmoeIy3NEk4XM7dluAfUcqa6b92bfb"
    Accept         = "application/json"
    "Content-Type" = "application/json"
}

# Listar repositórios
Invoke-RestMethod -Uri "$BASE/repositories" -Headers $H | ConvertTo-Json -Depth 5

# Ver health score
Invoke-RestMethod -Uri "$BASE/health-score/1" -Headers $H | ConvertTo-Json -Depth 5
```

---

## Fluxo completo de demonstração

Sequência recomendada para demonstrar a API ao vivo:

```bash
# 1. Cadastrar repositório
curl -sk -X POST $BASE/repositories -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"minha-api","full_name":"seu-usuario/minha-api","github_url":"https://github.com/seu-usuario/minha-api"}' | jq

# 2. Registrar pipelines (mix de sucesso e falha)
curl -sk -X POST $BASE/pipelines -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"repository_id":1,"workflow_name":"CI","status":"success","branch":"main","duration":120,"run_at":"2026-05-05 10:00:00"}' | jq .status
curl -sk -X POST $BASE/pipelines -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"repository_id":1,"workflow_name":"CI","status":"failure","branch":"main","duration":45,"run_at":"2026-05-06 09:00:00"}' | jq .status
curl -sk -X POST $BASE/pipelines -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"repository_id":1,"workflow_name":"CI","status":"success","branch":"main","duration":110,"run_at":"2026-05-06 10:00:00"}' | jq .status

# 3. Registrar release recente
curl -sk -X POST $BASE/releases -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"repository_id":1,"version":"v2.0.0","environment":"production","deployed_at":"2026-05-06 11:00:00","changelog":"Deploy inicial"}' | jq

# 4. Ver score (esperado ~93: CI 83% + deploy ok + sem incidentes)
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/health-score/1 | jq '{score,status}'

# 5. Simular webhook de pipeline via GitHub
curl -sk -X POST https://health-board-api.test/webhooks/github \
  -H "Content-Type: application/json" -H "X-GitHub-Event: workflow_run" \
  -d '{"repository":{"full_name":"seu-usuario/minha-api"},"workflow_run":{"name":"CI","status":"completed","conclusion":"success","head_branch":"main","run_started_at":"2026-05-06T12:00:00Z","updated_at":"2026-05-06T12:02:00Z"}}' | jq

# 6. Ver score atualizado
curl -sk -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" $BASE/health-score/1 | jq '{score,status}'
```
