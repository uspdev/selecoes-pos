# Sobre o projeto

Trata os fluxos de solicitações de isenção de taxa, inscrições e matrículas para processos seletivos da pós-graduação.
No contexto deste sistema, a matrícula é na verdade uma solicitação de matrícula, consistindo de uma submissão de formulário e de documentos à pós-graduação, que posteriormente realiza a matrícula propriamente dita no Janus.

# Características

Permite que candidatos solicitem isenção de taxa de inscrição ou matrícula, realizem inscrição e realizem matrícula.
Solicitações de isenção de taxa, inscrições e matrículas possuem cada qual seu fluxo de estados.
Para que um candidato solicite isenção de taxa, se inscreva ou se matricule, ele precisa estar logado, ou seja, ele deve antes realizar um cadastro local.
Os usuários locais são gravados também na tabela users, embora possuam modelo próprio no projeto.

Há duas categorias de processos seletivos: para aluno regular e para aluno especial.
No caso de aluno regular, as solicitações de isenção de taxa, inscrições e matrículas dizem respeito a um programa específico.
O aluno regular, ao se inscrever ou se matricular, deve escolher a combinação nível com linha de pesquisa/tema na qual está se inscrevendo ou se matriculando.
No caso de aluno especial, as seleções, solicitações de isenção de taxa, inscrições e matrículas não são atreladas a um programa.
O aluno especial, ao se matricular, deve escolher a(s) disciplina(s) na(s) qual(is) está se matriculando.

Pode-se configurar cada programa e também a categoria de aluno especial para utilizar o fluxo de inscrições, o fluxo de matrículas ou ambos. Essa flexibilidade é interessante para casos como, por exemplo, aluno regular para programas que dependam da FUVEST para realizar o processo seletivo; nestes casos, o programa utiliza este sistema somente para o fluxo de matrículas. Além disso, no caso de aluno regular, é interessante também haver o fluxo de matrículas para que os candidatos submetam os documentos necessários para a realização da matrícula propriamente dita.

Os gerentes devem cadastrar as seleções nas quais os candidatos solicitarão isenção de taxa, se inscreverão e se matricularão.
Cada seleção pode ter até três formulários para preenchimento pelo candidato (para a solicitação de isenção de taxa, para a inscrição e para a matrícula), gerados a partir de templates, e editáveis pelo gerente (excetos por campos utilizados pelo sistema, que não podem ser removidos, como CPF, e-mail, etc.).
O estado da seleção é modificado quando o gerente altera a data início/fim das solicitações de isenção de taxa, das inscrições ou das matrículas, também quando as seleções são consultadas (neste momento, o sistema verifica se alguma seleção passou da data início/fim, e muda o estado de acordo), e também quando um usuário inicia uma nova solicitação de isenção de taxa, inscrição ou matrícula.
O estado da seleção também é modificado quando o gerente sobe ou remove os documentos da seleção (edital, etc.), pois não podemos iniciar um período de solicitações de isenção de taxa, inscrições ou matrículas sem que a seleção tenha esses documentos.
Ao cadastrar uma nova seleção, o gerente deve informar a quais combinações de níveis com linhas de pesquisa/temas ela está atrelada (se a categoria da seleção for aluno regular, pois na categoria de aluno especial não temos combinações níveis com linhas de pesquisa/temas, o aluno especial se inscreve para disciplinas).
Ao cadastrar uma nova seleção com cobrança de taxa, todos os motivos de isenção de taxa são automaticamente associados à ela; cabe ao gerente verificar se é isso mesmo o desejado para a nova seleção.

A seleção pode ser normal ou de "fluxo contínuo". No caso normal, a data de vencimento do boleto é cadastrada na própria seleção; no caso de fluxo contínuo, os períodos dos fluxos disponíveis para o processo coincidem, e a data de vencimento do boleto é calculada somando-se uma determinada quantidade de dias úteis (quantidade essa cadastrada na seleção) acrescidos à data de envio da inscrição ou matrícula.
Os possíveis fluxos de estados de seleções sem cobrança de taxa são:
1) Em Elaboração -> Aguardando Início das Inscrições -> Período de Inscrições -> Encerrada
2) Em Elaboração -> Aguardando Início das Inscrições -> Período de Inscrições -> Aguardando Início das Matrículas -> Período de Matrículas -> Encerrada
3) Em Elaboração -> Aguardando Início das Matrículas -> Período de Matrículas -> Encerrada
Os possíveis fluxos de estados de seleções com cobrança de taxa são:
1) Em Elaboração -> Aguardando Início das Solicitações de Isenção de Taxa -> Período de Solicitações de Isenção de Taxa -> Aguardando Início das Inscrições -> Período de Inscrições -> Encerrada
2) Em Elaboração -> Aguardando Início das Solicitações de Isenção de Taxa -> Período de Solicitações de Isenção de Taxa -> Aguardando Início das Inscrições -> Período de Inscrições -> Aguardando Início das Matrículas -> Período de Matrículas -> Encerrada
3) Em Elaboração -> Aguardando Início das Solicitações de Isenção de Taxa -> Período de Solicitações de Isenção de Taxa -> Aguardando Início das Matrículas -> Período de Matrículas -> Encerrada
Os possíveis fluxos de estados de seleções com cobrança de taxa e fluxo contínuo são:
1) Em Elaboração -> Aguardando Início das Solicitações de Isenção de Taxa e das Inscrições -> Período de Solicitações de Isenção de Taxa e de Inscrições -> Encerrada
2) Em Elaboração -> Aguardando Início das Solicitações de Isenção de Taxa, das Inscrições e das Matrículas -> Período de Solicitações de Isenção de Taxa, de Inscrições e de Matrículas -> Encerrada
3) Em Elaboração -> Aguardando Início das Solicitações de Isenção de Taxa e das Matrículas -> Período de Solicitações de Isenção de Taxa e de Matrículas -> Encerrada

Há cinco funções para gestores: docentes do programa, secretários(as) dos programas, coordenadores dos programas, serviço de pós-graduação e coordenadores da pós-graduação.
Gestores das três primeiras funções são atrelados aos programas. Eles podem acessar seleções, solicitações de isenção de taxa, inscrições e matrículas somente de seus programas associados.
Gestores das duas últimas funções podem acessar todas as seleções, solicitações de isenção de taxa, inscrições e matrículas.

A função de docente corresponde ao perfil de docente. As funções de secretários(as) dos programas, coordenadores dos programas, serviço de pós-graduação e coordenadores da pós-graduação correspondem ao perfil de gerente. O conjunto de docentes, gerentes e admins forma o grupo de gestores.
Docentes têm acesso às inscrições e matrículas, mas somente de leitura.
Os docentes são definidos na mesma tabela que os gerentes, mas com a função docentes dos programas. No caso de matrículas de seleções para alunos especiais, os docentes não têm acesso.

No caso de se utilizar o fluxo de matrículas para aluno especial, há uma fase inicial adicional em que o candidato entra em contato com o docente para obter sua aprovação (a secretaria de pós-graduação chama esse passo de "inscrição"); isso é feito por e-mail, fora deste sistema. Em seguida, o candidato solicita a isenção de taxa e, por fim, realiza sua matrícula (submissão de formulário e documentos à pós-graduação através deste sistema); a secretaria de pós-graduação realiza a matrícula dele no período de matrículas do sistema (esta é a matrícula propriamente dita, no Janus). Portanto, este sistema trata a segunda e a terceira fases do processo para aluno especial.

As linhas de pesquisa/temas são relacionadas aos níveis da pós-graduação (mestrado, doutorado, doutorado direto).
Se um aluno regular se inscreve ou se matricula, por exemplo, para o nível de mestrado, só lhe serão permitidas as linhas de pesquisa/temas desse programa dessa seleção que estejam relacionadas ao nível escolhido. O gerente só pode acessar as linhas de pesquisa/temas de seu programa.

Para as disciplinas, os acessos são abertos: qualquer gerente pode acessar disciplinas de quaisquer programas. Na verdade, as disciplinas nem estão atreladas a programas, embora isso talvez fosse possível, mas não se mostrou necessário, por serem de escolha exclusiva de aluno especial, que não opta por programa.

Cada seleção contém informativos (edital, etc.), que são documentos que o candidato pode consultar.
Além disso, em cada seleção o gerente também define quais documentos o candidato pode (ou deve) subir quando da solicitação de isenção de taxa, inscrição e matrícula.
O tipo de documento de boletos não é removível nem renomeável. O candidato não sobe documento desse tipo, pois ele é gerado quando do envio ou aprovação da inscrição ou matrícula, dependendo de como o sistema está configurado.
Inscrições e matrículas para programas podem ser de três níveis diferentes (mestrado, doutorado ou doutorado direto) e os tipos de documento dessas inscrições podem variar conforme o nível e o programa. Tipos de documento com diferenciação por níveis e programas é algo que só faz sentido nas inscrições e matrículas. Para que um tipo de documento apareça na solicitação de isenção de taxa, inscrição ou matrícula, devemos cadastrá-lo na relação de Tipos de Documento; devemos também cadastrar suas relações com combinações de nível com tipo de programa; por fim, devemos cadastrá-lo também na seleção em questão (como tipo de documento para solicitação de isenção de taxa, inscrição ou matrícula).

Para completar a inscrição ou matrícula, o candidato deve clicar em Enviar Inscrição ou Enviar Matrícula.
Se houver cobrança de taxa de inscrição ou matrícula para a seleção em questão, é gerado um boleto e enviado por e-mail para o candidato pagar essa taxa. A geração e envio do boleto ocorre no envio ou aprovação da inscrição/matrícula, conforme o sistema estiver configurado.
No caso de aluno regular, é gerado um único boleto.
No caso de aluno especial, é gerado um boleto para cada disciplina na qual ele se inscreveu, e enviado para o candidato um único e-mail com todos esses boletos anexados.
Algumas informações necessárias para a geração de boletos se encontram na tabela "parametros", que é editável pelos admins em tela.
Quando o sistema está configurado para gerar e enviar boleto no momento do envio da inscrição/matrícula, caso um candidato reenvie sua matrícula, e ela seja para uma seleção de categoria aluno especial, e as disciplinas para as quais ele está se matriculando tenham sido alteradas em relação ao envio anterior, o sistema irá gerar e enviar boletos para as novas disciplinas, além de marcar como boletos referentes a disciplinas removidas aqueles boletos que haviam sido gerados anteriormente e cujas disciplinas o candidato removeu nesse reenvio da matrícula.
Quando o processo seletivo permite os fluxos tanto de inscrição quanto de matrícula, o sistema gera e envia boletos somente no fluxo de inscrição, não o fazendo no fluxo de matrícula.

A lei 14.534/2023 estabeleceu que estrangeiros devem possuir CPF para cursar pós-graduação. Com base nisso, passamos a utilizar o CPF como identificador único de pessoas nas situações em que precisamos identificar a mesma pessoa tendo realizado ações em momentos diferentes. Antes nos baseávamos no usuário autor, o que poderia levar a erros caso um usuário solicitasse isenção de taxa, efetuasse inscrição ou matrícula para outro candidato.

# Envios de e-mails

1) quando um gerente completa a elaboração de uma seleção, o sistema envia um e-mail para o gerenciamento do site da unidade avisando-o para atualizar a página da seleção no site da unidade;
2) quando um candidato realiza seu cadastro, o sistema lhe envia um e-mail com um link para confirmar o endereço de e-mail;
3) quando um candidato clica em "Esqueceu sua senha", o sistema lhe envia um e-mail com um link para resetar a senha;
4) quando um candidato inicia sua solicitação de isenção de taxa (clicando em "Prosseguir" mas ainda não em "Enviar Solicitação"), o sistema lhe envia um e-mail avisando sobre a necessidade de subir os documentos obrigatórios e de enviar a solicitação de isenção de taxa;
5) quando um candidato envia sua solicitação de isenção de taxa, o sistema lhe envia um e-mail informando do sucesso;
6) ainda quando um candidato envia sua solicitação de isenção de taxa, o sistema também envia um e-mail para o serviço de pós-graduação avisando sobre a solicitação de isenção de taxa;
7) quando um gerente aprova/rejeita uma solicitação de isenção de taxa, o sistema envia um e-mail para o candidato avisando a respeito da aprovação/rejeição;
8) quando um candidato inicia sua inscrição ou matrícula (clicando em "Prosseguir" mas ainda não em "Enviar Inscrição" ou "Enviar "Matrícula"), o sistema lhe envia um e-mail avisando sobre a necessidade de subir os documentos obrigatórios e de enviar a inscrição ou matrícula;
9) quando um candidato envia sua inscrição ou matrícula, o sistema lhe envia um e-mail informando do sucesso e, se o sistema estiver configurado para enviar boleto no envio da inscrição/matrícula, o sistema anexa nesse e-mail o(s) boleto(s) a ser(em) pago(s);
10) ainda quando um candidato a aluno regular envia sua inscrição (não matrícula!), o sistema também envia um e-mail para a secretaria do programa avisando sobre a inscrição;
11) ainda quando um candidato a aluno regular envia sua inscrição (não matrícula!), o sistema também envia um e-mail para cada coordenador do programa avisando sobre a inscrição;
12) ainda quando um candidato a aluno especial envia sua inscrição (não matrícula!), o sistema também envia um e-mail para cada pessoa do serviço de pós-graduação avisando sobre a matrícula;
13) ainda quando um candidato envia sua matrícula (não inscrição!), o sistema também envia um e-mail para cada pessoa do serviço de pós-graduação avisando sobre a matrícula;
14) quando um candidato reenvia sua inscrição ou matrícula alterando as disciplinas para as quais se inscreveu ou matriculou, o sistema lhe envia um e-mail informando do sucesso e, se o sistema estiver configurado para enviar boleto no envio da inscrição/matrícula, o sistema anexa nesse e-mail o(s) boleto(s) da(s) nova(s) disciplina(s);
15) quando um gerente clica em enviar um boleto de uma inscrição ou matrícula, o sistema envia um e-mail para o candidato enviando o boleto (isso é interessante para o caso de eventualmente o envio do boleto ter falhado quando o candidato enviou sua inscrição ou matrícula);
16) quando um gerente pré-aprova uma inscrição ou matrícula, o sistema envia um e-mail para o candidato avisando a respeito da pré-aprovação;
17) quando um gerente pré-reprova uma inscrição ou matrícula, o sistema envia um e-mail para o candidato avisando a respeito da pré-rejeição;
18) quando um gerente aprova uma inscrição ou matrícula, o sistema envia um e-mail para o candidato avisando a respeito da aprovação e, quando o sistema está configurado para enviar boleto na aprovação da inscrição/matrícula, esse e-mail para o candidato vai com o(s) boleto(s) a ser(em) pago(s);
19) quando um gerente rejeita uma inscrição ou matrícula, o sistema envia um e-mail para o candidato avisando a respeito da rejeição; 
20) quando um gerente sobe um documento dos tipos "Errata" ou "Resultado" em uma seleção, o sistema envia e-mails para cada candidato avisando a respeito desses novos informativos;
21) uma semana após um candidato ter iniciado uma solicitação de isenção de taxa mas não tê-la concluído, no caso de seleção com fluxo contínuo, o sistema lhe envia e-mail lembrando-o de concluir o processo;
22) uma semana após um candidato ter iniciado uma inscrição mas não tê-la concluído, no caso de seleção com fluxo contínuo, o sistema lhe envia e-mail lembrando-o de concluir o processo;
23) uma semana após um candidato ter iniciado uma matrícula mas não tê-la concluído, no caso de seleção com fluxo contínuo, o sistema lhe envia e-mail lembrando-o de concluir o processo;
24) quando nos aproximamos do término do período de solicitações de isenção de taxa de uma seleção, o sistema envia e-mails para cada candidato que iniciou mas não enviou sua solicitação de isenção de taxa, lembrando-os de concluir os processos;
25) quando nos aproximamos do término do período de inscrições de uma seleção, o sistema envia e-mails para cada candidato que iniciou mas não enviou sua inscrição, lembrando-os de concluir os processos.
26) quando nos aproximamos do término do período de matrículas de uma seleção, o sistema envia e-mails para cada candidato que iniciou mas não enviou sua matrícula, lembrando-os de concluir os processos.

Todo e qualquer e-mail enviado pelo sistema pode ser copiado (em cópia oculta) para o e-mail de envio do sistema. O endereço de envio de e-mail do sistema está definido no .env, e também está no .env essa configuração de copiar para esse remetente ou não.
Desta forma, podemos ter um histórico de todos os e-mails enviados pelo sistema, embora na caixa de entrada ao invés de na caixa de enviados.

Os fluxos são, grosso modo, o conjunto de operações efetuadas pelo sistema, compreendendo envios de e-mails, geração de boletos, alteração nos estados. São três: fluxo de solicitação de isenção de taxa, fluxo de inscrição e fluxo de matrícula.
Praticamente para tudo que o sistema deve realizar, ele se baseia em qual é o fluxo, exceto para trabalhar com linhas de pesquisa/temas ou disciplinas (neste caso, o sistema verifica a categoria: se for aluno regular, trabalha com linhas de pesquisa/temas; se for aluno especial, trabalha com disciplinas).

# Fluxo 1: solicitação de isenção de taxa

1) o candidato envia a solicitação;
2) um e-mail é enviado ao candidato reconhecendo o envio da solicitação;
3) um e-mail é enviado ao serviço de pós-graduação para avaliar a solicitação;
4) o serviço de pós-graduação coloca a solicitação em avaliação e depois a aprova ou rejeita;
5) um e-mail é enviado ao candidato avisando da aprovação ou rejeição da solicitação;
6) caso a solicitação tenha sido rejeitada e posteriormente aprovada devido a recurso do candidato, um e-mail é enviado ao candidato avisando da aprovação após recurso.

# Fluxo 2: inscrição

1) o candidato envia a inscrição;
2) um e-mail é enviado ao candidato reconhecendo o envio da inscrição e, caso o sistema esteja configurado para enviar boleto no envio da inscrição, ele recebe junto o(s) eventual(is) boleto(s) da taxa de inscrição a pagar;
3) um e-mail é enviado à secretaria do programa e aos coordenadores do programa para pré-avaliar a inscrição;
4) a secretaria do programa ou os coordenadores do programa colocam a inscrição em pré-avaliação e depois a pré-aprovam ou pré-rejeitam;
5) em caso de pré-aprovação, um e-mail é enviado ao candidato compartilhando o endereço no site da unidade para acompanhamento do processo pelos candidatos, na seção do programa correspondente ou aluno especial;
6) em caso de pré-rejeição, um e-mail é enviado ao candidato avisando-o da pré-rejeição;
7) a secretaria do programa coloca a inscrição em avaliação e depois a aprova ou rejeita;
8) um e-mail é enviado ao candidato avisando da aprovação ou rejeição da inscrição e, caso seja aprovação, e caso o sistema esteja configurado para enviar boleto na aprovação da inscrição, ele recebe junto o(s) eventual(is) boleto(s) da taxa de inscrição a pagar.

# Fluxo 3: matrícula

1) o candidato envia a matrícula;
2) um e-mail é enviado ao candidato reconhecendo o envio da matrícula e, caso o sistema esteja configurado para enviar boleto no envio da matrícula, ele recebe junto o(s) eventual(is) boleto(s) da taxa de matrícula a pagar;
3) um e-mail é enviado ao serviço de pós-graduação para pré-avaliar a matrícula;
4) o serviço de pós-graduação coloca a matrícula em pré-avaliação e depois a pré-aprova ou pré-rejeita;
5) em caso de pré-aprovação, um e-mail é enviado ao candidato compartilhando o endereço no site da unidade para acompanhamento do processo pelos candidatos, na seção do programa correspondente ou aluno especial;
6) em caso de pré-rejeição, um e-mail é enviado ao candidato avisando-o da pré-rejeição;
7) o serviço de pós-graduação coloca a matrícula em avaliação e depois a aprova ou rejeita;
8) um e-mail é enviado ao candidato avisando da aprovação ou rejeição da matrícula e, caso seja aprovação, e caso o sistema esteja configurado para enviar boleto na aprovação da matrícula, ele recebe junto o(s) eventual(is) boleto(s) da taxa de matrícula a pagar.

## Changelog

Veja o [histórico de atualizações](docs/changelog.md).

## Requisitos

Este sistema foi projetado para rodar em servidores linux (Ubuntu e Debian).

-   Laravel 12
-   PHP 8.3
-   Apache ou Nginx
-   Banco de dados local (MariaDB mas pode ser qualquer um suportado pelo Laravel)
-   Git
-   Composer
-   Credenciais para senha única
-   Acesso ao replicado (visão Pessoa - VUps, Estrutura - VUes e Financeiro - VUfi)

Bibliotecas necessárias do php:

    apt install php-sybase php-mysql php-xml php-intl php-mbstring php-gd php-curl php-zip php-soap

Descomentar a linha extension=soap do php.ini    

## Atualização

Caso você já tenha instalado o sistema e aplique uma nova atualização, sempre deve rodar:

    composer install --no-dev
    php artisan migrate

Também deve observar no [changelog](docs/changelog.md) se tem alguma outra coisa a ser ajustada, por exemplo o arquivo .env

## Instalação

    cd /var/www/html
    git clone git@github.com:USPdev/selecoes-pos
    cd selecoes-pos
    composer install
    cp .env.example .env
    php artisan key:generate

Criar user e banco de dados (em mysql):

    sudo mysql
    create database selecoespos;
    create user 'selecoespos'@'%' identified by '<<password here>>';    # nunca utilizar @ dentro da senha, pois dá erro no servidor de produção ao acessar o banco
    grant all privileges on selecoespos.* to 'selecoespos'@'%';
    flush privileges;

#### ################################ ####
## Configuração em ambiente de produção ##
#### ################################ ####

### Configurar o cache

A biblioteca (https://github.com/uspdev/cache) usada no replicado utiliza o servidor memcached. Se você pretende utilizá-lo instale e configure ele:

    sudo apt install memcached
    sudo vim /etc/memcached.conf
        I = 5M
        -m 128

    /etc/init.d/memcached restart

### E-mail

Configurar a conta de e-mail para acesso menos seguro pois a conexão é via smtp.

### Configurar o apache ou nginx

Criar novo arquivo selecoes-pos.conf em /etc/apache2/sites-available; nele, dentro da tag VirtualHost, o DocumentRoot deve apontar para /var/www/html/selecoes-pos/public. E para que as rotas funcionem, adicionar, ainda dentro dessa tag, a seguinte configuração:

    <Directory /var/www/html/selecoes-pos/public>
        AllowOverride All
    </Directory>

E, em seguida, executar:

    sudo a2enmod rewrite
    sudo service apache2 restart

No Apache é possivel utilizar a extensão MPM-ITK (http://mpm-itk.sesse.net/) que permite rodar seu _Servidor Virtual_ com usuário próprio. Isso facilita rodar o sistema como um usuário comum e não precisa ajustar as permissões da pasta `storage/`.

    sudo apt install libapache2-mpm-itk
    sudo a2enmod mpm_itk                        # habilita o módulo
    sudo service apache2 restart

Dentro do selecoes-pos.conf, dentro da tag VirtualHost coloque:

    <IfModule mpm_itk_module>
        AssignUserId nome_do_usuario nome_do_grupo
    </IfModule>

### Configurar senha única

Cadastre uma nova URL no configurador de senha única utilizando o caminho `https://seu_app/callback`. Guarde o callback_id para colocar no arquivo `.env`.

### Edite o arquivo .env

Há várias opções que precisam ser ajustadas nesse arquivo. Faça com atenção para não deixar passar nada. O arquivo está todo documentado.

### Popular banco de dados

    php artisan migrate

Os setores e respectivos designados podem ser importados do Replicado. Para isso rode:

    php artisan db:seed

Depois de importado faça uma conferência para não haver inconsistências.

### Seeders e Generalização do Sistema

Foi criado um seeder central para automatizar a população do banco de dados com informações da unidade configurada no `.env`.

Os dados são consumidos dinamicamente do **Replicado** e do **Cadastros Auxiliares**.
* **Dados dinâmicos por unidade:** Programas, Disciplinas e Docentes.
* **Dados gerais: (não dependem do Replicado)** Feriados, Permissões (*Permissions*), Setores Replicados, Categorias e Níveis.

Para executar este seeder, utilize o comando:

        php artisan db:seed

**Generalização de Unidade no Template:** Para tornar o sistema compatível com diferentes unidades, a model `Selecao` utiliza o método `injetarUnidadeNoTemplate`. Esse método intercepta a criação de uma nova Seleção e substitui automaticamente os textos padrão pelo nome oficial da unidade (obtido dinamicamente via configurações do sistema).

### Instalar e configurar o Supervisor

Para as filas de envio de e-mail, o sistema precisa de um gerenciador que mantenha rodando o processo que monitora as filas. O recomendado é o **Supervisor**. No Ubuntu ou Debian instale com:

    sudo apt install supervisor

Modelo de arquivo de configuração. Como **`root`**, crie o arquivo `/etc/supervisor/conf.d/selecoes_pos_queue_worker_default.conf` com o conteúdo abaixo:

    [program:selecoes_pos_queue_worker_default]
    command=/usr/bin/php /var/www/html/selecoes-pos/artisan queue:listen --queue=default --tries=3 --timeout=60
    process_num=1
    username=www-data
    numprocs=1
    process_name=%(process_num)s
    priority=999
    autostart=true
    autorestart=unexpected
    startretries=3
    stopsignal=QUIT
    stderr_logfile=/var/www/html/selecoes-pos/storage/logs/selecoes_pos_queue_worker_default.log

Ajustes necessários:

    command=<ajuste o caminho da aplicação>
    username=<nome do usuário do processo do selecoes-pos>
    stderr_logfile = <aplicacao>/storage/logs/<seu arquivo de log>

Reinicie o **Supervisor**

    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl restart all

### Permissão de escrita na pasta 'storage' ao usuário do browser:

É necessária essa permissão, pois o site utiliza sessões, que são gravadas em storage/framework/sessions.
E se ligarmos o modo debug, o site também quer gravar em storage/logs.

    sudo chown -R www-data:www-data /var/www/html/selecoes-pos/storage
    sudo chmod -R 755               /var/www/html/selecoes-pos/storage
    sudo service apache2 restart

#### ################### ####
## Atualização em produção ##
#### ################### ####

Para receber as últimas atualizações do sistema rode:

    cd /var/www/html/selecoes-pos
    git pull
    composer install --no-dev
    php artisan migrate

Para atualizar os pacotes utilizados pelo sistema (por exemplo, o laravel-usp-theme), rode:

    composer update

Caso tenha alguma atualização, não deixe de conferir o readme.md quanto a outras providências que podem ser necessárias.

#### ####################################### ####
## Configuração em ambiente de desenvolvimento ##
#### ####################################### ####

Ainda é preciso descrever melhor mas pode seguir as instruções para ambiente de produção com os ajustes necessários.

    php artisan migrate:fresh --seed

O senhaunica-fake pode não ser adequado pois o sistema coloca as pessoas nos respectivos setores com as informações da senha única.

Para subir o servidor

    php artisan serve

**CUIDADO**: você pode enviar e-mails indesejados para as pessoas.

Para enviar e-mails e executar jobs agendadas é necessário executar as tarefas na fila. Para isso, em outro terminal, execute:

    php artisan queue:listen

## Problemas e soluções

Ao rodar pela primeira vez com apache, as variáveis de ambiente relacionadas ao replicado não ficam disponíveis. Nesse caso é necessário restartar o apache.

https://www.php.net/manual/pt_BR/function.getenv.php#117301

Para limpar e recriar todo o DB, rode sempre que necessário:

    php artisan migrate:fresh --seed

## Histórico

-   27/03/2025
    -   versão 1.0.0
-   27/05/2025
    -   versão 1.4.0 - atualizado de Laravel 11 para 12

## Detalhamento técnico

Foram utilizados vários recursos do laravel que podem não ser muito trivial para todos.

-   As jobs agendadas e os e-mails a enviar são colocados em filas; para isso, precisamos utilizar em produção o supervisor e em desenvolvimento o comando php artisan queue:listen; no arquivo .env configuramos QUEUE_CONNECTION=database para que todas as jobs agendadas sejam gravados na tabela jobs do banco de dados; por outro lado, as jobs em que usamos dispatch()->onConnection('sync') são executadas imediatamente; em desenvolvimento, ao executar o comando php artisan queue:listen, todas as jobs atrasadas presentes na tabela jobs do banco de dados são executadas imediatamente, e as jobs programadas para o futuro serão executadas na data e hora programadas.

-   O sistema faz uso dos seguintes serviços externos: WSBoleto da USP, Recaptcha v2 do Google e Viacep (que é gratuito, diferente do webservice dos Correios, que exige convênio específico).

-   Quase a totalidade da implementação deste sistema foi inspirado no chamados; muito código foi copiado de lá, e adaptado: as solicitações de isenção de taxa, inscrições e matrículas deste sistema são de certa forma similares aos chamados do sistema de chamados, as seleções deste sistema são de certa forma similares às filas do sistema de chamados, e os programas deste sistema são de certa forma similares aos setores do sistema de chamados.

-   A tela de funções foi inspirada no datagrad, embora a implementação tenha sido nova.

-   O gerenciamento de usuários locais por admins foi inspirado no impressoras.

-   Como este sistema utiliza Laravel 11, alguns comandos tiveram que ser reescritos em relação ao sistema de chamados em Laravel 8. A biblioteca laravelcollective\html foi deprecada, e passamos a utilizar a biblioteca spatie\laravel-html. Com isso, por exemplo, a antiga sintaxe que era assim:
    {!! Form::open(['url' => 'chamados']) !!}
passou a ser assim:
    {{ html()->form('post', 'inscricoes')->open() }}

-   Este sistema foi atualizado para Laravel 12 em 27/05/2025; não foram necessárias alterações no código.

-   Em sua versão inicial, os seeders continham dados específicos para o IPUSP. Em um momento posterior, ao adaptá-lo para a ECA, os seeders com dados específicos foram apagados. Os demais seeders (feriados, níveis, etc.) não precisam ser alterados.
