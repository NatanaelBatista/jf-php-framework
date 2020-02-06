JF Framework PHP
===

O JF Framework é um framework para apaixonados por programação em PHP, combinando simplicidade, praticidade, produtividade e robustez. Foi construído para programadores intensos em suas crenças e profundos em suas convicções.

Motivação
---

Em 2015, precisei definir o framework oficial para os próxmos projetos da equipe. A equipe era pequena (3 pessoas ao todo) e a rotatividade dos estagiários era alta. Estabelecemos alguns critérios de seleção do framework:
- **fácil e rápido pra instalar** - não pode depender do composer, um simples dowload resolve.
- **fácil e rápido pra configurar** - pouca configuração para começar a codificar.
- **fácil e rápido de aprender** - utilizar poucos componentes e menos linhas de código.
- **fácil de manter** - os artefatos devem estar bem distribuídos e ser fáceis de modificar.
- **todo tipo de aplicação** - simples o suficiente para aplicações pequenas, robusto e flexível o bastante para grandes aplicações.
- **documentação suficiente** - a documentação deve ser suficiente para aprender, sem precisar recorrer à comunidade.

Nenhum framework de mercado passou no crivo.

Filosofias e premissas do framework
---

Assim nasceu a necessidade de criar o framework que atendesse aos critérios acima mencionados. Ao longo do desenvolvimento, algumas sacadas foram sendo percebidas e implementadas, que o torna diferente de qualquer outro que você já conheceu:

- Menos camadas é mais simples e melhor pra manter
- arquivos de artefatos relacionados devem ficar próximos.
- os melhores padrões de projeto são os mais simples de entender.
- o core do framework deve ser simples o suficiente para ser facilmente entendido por quem não o escreveu.
- PHP cuida do backend e HTML, CSS e Javascript cuidam do frontend.
- Coisas óbvias devem ser automatizadas.
- Heranças e traits funcionam melhor que interfaces.

Alguns dos recursos mais interessantes que tornam o desenvolvimento com JF Framework uma experiência única:
---

- Rotas automáticas
- Classe única por chamada HTTP
- Classes individuais para cada regra de negócio
- Automação na validação de dados informados pelo usuário
- Montador de documentação automática
- Montar templates de páginas complexas como um lego
- WebComponents modularizado nativo
- Micro ORM
- Frontend portátil (se você copiar a pasta do frontend pra sua área de trabalho, as páginas funcionam)
- Criação de páginas HTML estáticas
- Interpretador inteligente de inputs (GET/POST/ARGS) e formatos de resposta das requisições (TEXT/PHP/JSON/XML/CSV/XLS/DOWNLOAD)

Arquitetura
---

Está construído sob a arquitetura mais moderna da atualidade - FDD (*Feature-driven development*). Está distribuído em 3 camadas principais: Features, DTOs e Views (templates).

- **Features** - Funcionalidades (C do MVC).
- **DTOs** - Acesso a dados (M do MVC).
- **Templates** - Montagem de páginas (V do MVC).

Documentação
---

A documentação pode ser encontrada na pasta guide do próprio framework. Boa leitura.
