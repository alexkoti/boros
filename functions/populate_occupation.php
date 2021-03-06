<?php
function load_occupations( $format = 'list' ){
	$occupations = array();
	$occupations['a'] = array(
		'acompanhante',
		'acordeonista',
		'açougueiro',
		'acrobata',
		'acupunturista',
		'adivinho',
		'administrador',
		'advogado',
		'aeromoça',
		'afinador',
		'agrimensor',
		'agrônomo',
		'aguadeiro',
		'aguazil',
		'ajudante',
		'alcatifeiro',
		'alfaiate',
		'alfandegueiro',
		'algibebe',
		'alguazil',
		'almotacé',
		'almotacel',
		'almoxarife',
		'alpargateiro',
		'alpinista',
		'alvazir',
		'ama',
		'ambientalista',
		'ambulante',
		'amolador',
		'anestesista',
		'antiquário',
		'antropologista',
		'antropólogo',
		'apicultor',
		'apresentador',
		'arameiro',
		'arcebispo',
		'armeiro',
		'arqueiro',
		'arqueólogo',
		'arquiteto',
		'arquivista',
		'arte-finalista',
		'artesão',
		'artífice',
		'artilheiro',
		'assessor',
		'astrólogo',
		'astronauta',
		'astrônomo',
		'atendente',
		'atleta',
		'ator',
		'atuário',
		'auditor',
		'automobilista',
		'autônomo',
		'auxiliar',
		'aviador',
		'avicultor',
	);
	$occupations['b'] = array(
		'babá',
		'bailarino',
		'baixista',
		'balanceiro',
		'balconista',
		'bancário',
		'banqueiro',
		'barbeiro',
		'barista',
		'baterista',
		'bibliotecário',
		'biologista',
		'biólogo',
		'biomédico',
		'bispo',
		'bombeiro',
		'borracheiro',
		'bufarinheiro',
	);
	$occupations['c'] = array(
		'cabeleireiro',
		'caçador',
		'cacimbeiro',
		'cafetão',
		'caiador',
		'caixa',
		'caixoteiro',
		'calafate',
		'calceteiro',
		'cambista',
		'camelô',
		'canalizador',
		'cantor',
		'capataz',
		'capitão',
		'carcereiro',
		'cardador',
		'cardiologista',
		'caricaturista',
		'carimbador',
		'carpinteiro',
		'carteiro',
		'cartonageiro',
		'cartunista',
		'carvoeiro',
		'catedrático',
		'cavaleiro',
		'cenarista',
		'cenógrafo',
		'cenotécnico',
		'chofer',
		'cientista',
		'cineasta',
		'cirurgião',
		'clarinetista',
		'climatologista',
		'clínico',
		'colecionador',
		'comediante',
		'comentarista esportivo',
		'compositor',
		'condicionador físico',
		'consultor',
		'contador',
		'contrabaixista',
		'contrarregra',
		'controlador',
		'coralista',
		'coreógrafo',
		'corista',
		'cornaca',
		'correeiro',
		'corregedor',
		'correspondente',
		'cortador',
		'costureiro',
		'coveiro',
		'cozinheiro',
		'criado',
		'criminólogo',
		'crítico',
		'curandeiro',
		'cuscuzeiro'
	);
	$occupations['d'] = array(
		'dançarino',
		'datilógrafo',
		'decorador',
		'defumador',
		'delegado',
		'dentista',
		'deputado',
		'dermatologista',
		'desembargador',
		'desenhador',
		'desenhista',
		'desentupidor',
		'designer',
		'despachante',
		'desportista',
		'detetive',
		'diácono',
		'dicionarista',
		'diretor',
		'docente',
		'documentalista',
		'domador',
		'doméstico',
		'dona de casa',
		'dourador',
		'doutor',
		'doutrinador',
		'droguista'
	);
	$occupations['e'] = array(
		'economista',
		'editor',
		'educador',
		'egiptólogo',
		'eletricista',
		'embaixador',
		'empacotador',
		'empregada doméstica',
		'empregada',
		'empresário',
		'encadernador',
		'enciclopedista',
		'enfermeiro',
		'engenheiro-residente',
		'engenheiro',
		'entomologista',
		'entomólogo',
		'entregador',
		'enxadrista',
		'escriba',
		'escritor',
		'escrivão',
		'esparteiro',
		'espeleologista',
		'espião',
		'esportista',
		'esquiador',
		'esteireiro',
		'estenógrafo',
		'estilista',
		'estivador',
		'estudante',
		'exorcista',
		'explorador'
	);
	$occupations['f'] = array(
		'fabricante',
		'fadista',
		'fagotista',
		'fanqueiro',
		'farmacêutico',
		'faroleiro',
		'faturista',
		'fazendeiro',
		'feiticeiro',
		'feitor',
		'ferrageiro',
		'ferreiro',
		'figurante',
		'figurinista',
		'filantropo',
		'filatelista',
		'filólogo',
		'filósofo',
		'fiscal',
		'fiscalista',
		'fiscalizador',
		'físico',
		'fisiologista',
		'fisioterapeuta',
		'flautinista',
		'flautista',
		'florista',
		'fogueteiro',
		'fonoaudiólogo',
		'forcado',
		'formador',
		'fotocompositor',
		'fotógrafo',
		'frentista',
		'fresador',
		'fretista',
		'funâmbulo',
		'funcionário',
		'fundidor',
		'funileiro',
		'futebolista',
		'fuzileiro'
	);
	$occupations['g'] = array(
		'gandula',
		'gigolo',
		'garagista',
		'garçom',
		'garçonete',
		'gari',
		'garimpeiro',
		'gaspeadeira',
		'general',
		'geneticista',
		'geógrafo',
		'geólogo',
		'geômetra',
		'geotécnico',
		'gerente',
		'geriatra',
		'gestor',
		'ginasta',
		'ginecologista',
		'governador',
		'governanta',
		'governante',
		'grafologista',
		'gramático',
		'gravador',
		'guadamecileiro',
		'guarda-balizas',
		'guarda-vidas',
		'guia',
		'guitarrista'
	);
	$occupations['h'] = array(
		'hagiógrafo',
		'halterofilista',
		'harpista',
		'hematólogo',
		'herborista',
		'hidrógrafo',
		'higienista',
		'historiador',
		'horticultor',
		'hoteleiro',
		'humorista'
	);
	$occupations['i'] = array(
		'ictiólogo',
		'ilusionista',
		'imediato',
		'imperador',
		'impositor',
		'impressor',
		'informático',
		'inspetor',
		'instrutor',
		'intendente',
		'inventor',
		'investigador'
	);
	$occupations['j'] = array(
		'jangadeiro',
		'jardineiro',
		'jazzista',
		'jesuita',
		'joalheiro',
		'jogador',
		'jornaleiro',
		'jornalista',
		'judoca',
		'juiz',
		'jurista',
		'justiceiro',
	);
	$occupations['l'] = array(
		'lanterneiro',
		'lanterninha',
		'lavadeira',
		'lavrador',
		'ledor',
		'legista',
		'leiloeiro',
		'leiteiro',
		'lenhador',
		'lexicógrafo',
		'linotipista',
		'liteireiro',
		'litógrafo',
		'livreiro',
		'lixeiro',
		'lojista',
	);
	$occupations['m'] = array(
		'maestro',
		'mágico',
		'magistrado',
		'malabarista',
		'maleiro',
		'manicure',
		'manobrista',
		'maquetista',
		'maquiador',
		'maquinista',
		'marceneiro',
		'marechal',
		'marimbeiro',
		'marinheiro',
		'marisqueira',
		'massagista',
		'mastologista',
		'matemático',
		'mecânico',
		'médico',
		'meeiro',
		'mercador',
		'merceeiro',
		'mergulhador',
		'mesteiral',
		'meteorologista',
		'michê',
		'militar',
		'mineiro',
		'ministro',
		'modelo',
		'moleiro',
		'monotipista',
		'montador',
		'motorista',
		'mulher-a-dias',
		'musicista',
		'músico',
	);
	$occupations['n'] = array(
		'nadador-salvador',
		'nadador',
		'narcotraficante',
		'narrador',
		'naturalista',
		'navegador',
		'nefrologista',
		'neurologista',
		'notário',
		'novelista',
		'numismata',
		'nutricionista',
	);
	$occupations['o'] = array(
		'oboísta',
		'obreiro',
		'obstetra',
		'oculista',
		'odontologista',
		'odontólogo',
		'office-boy',
		'oftalmologista',
		'oleiro',
		'oncologista',
		'operador',
		'operário',
		'óptico',
		'optometrista',
		'orador',
		'organista',
		'ornamentador',
		'ornamentista',
		'ornitologista',
		'ornitólogo',
		'ortodontista',
		'ortopedista',
		'ostreicultor',
		'oticista',
		'otorrinolaringologista',
		'ourives',
	);
	$occupations['p'] = array(
		'padeiro',
		'padre',
		'paginador',
		'palhaço',
		'papirólogo',
		'paramédico',
		'paraquedista',
		'parteira',
		'passista',
		'pasteleiro',
		'pastor',
		'patinador',
		'patrão',
		'patrulheiro',
		'pediatra',
		'pedicuro',
		'pedreiro',
		'peixeiro',
		'peleiro',
		'perito',
		'pescador',
		'pesquisador',
		'petroquímico',
		'pianista',
		'piloto',
		'pintor',
		'pizzaiolo',
		'policial',
		'pombeiro',
		'porteiro',
		'praticante',
		'prático',
		'prefeito',
		'presidente',
		'processador',
		'proctologista',
		'procurador',
		'produtor',
		'professor',
		'programador',
		'projecionista',
		'promotor',
		'prostituta',
		'proxeneta',
		'psicanalista',
		'psicólogo',
		'psiquiatra',
		'publicitário',
		'pugilista',
	);
	$occupations['q'] = array(
		'quadrinista',
		'queijeiro',
		'químico',
		'quiropata',
	);
	$occupations['r'] = array(
		'radialista',
		'radiologista',
		'realizador',
		'recepcionista',
		'rei',
		'reitor',
		'relator',
		'relojoeiro',
		'remador',
		'rendeira',
		'repórter',
		'restaurador',
		'retocador',
		'retroseiro',
		'reumatologista',
		'revisor',
		'rifeiro',
		'rodoviário',
		'roteirista',
	);
	$occupations['s'] = array(
		'sacristão',
		'salva-vidas',
		'sambista',
		'sanfoneiro',
		'sapateiro',
		'saxofonista',
		'secretário',
		'segurança',
		'senador',
		'serigrafista',
		'seringueiro',
		'serralheiro',
		'sexólogo',
		'sismólogo',
		'soldado',
		'solista',
		'superintendente',
		'supervisor',
		'surfaçagista',
	);
	$occupations['t'] = array(
		'taberneiro',
		'talhante',
		'tamboril',
		'tanatopraxista',
		'tanoeiro',
		'tapeceiro',
		'tatuador',
		'taxidermista',
		'taxista',
		'tecladista',
		'técnico',
		'tenista',
		'teólogo',
		'terapeuta da fala',
		'terapeuta',
		'timoneiro',
		'timpanista',
		'tintureiro',
		'tipógrafo',
		'tisiologista',
		'topógrafo',
		'torcedor',
		'toureiro',
		'toxicógrafo',
		'toxicologista',
		'toxicólogo',
		'tradutor',
		'transitário',
		'transportador',
		'tratador',
		'trombonista',
		'trompetista',
		'trovador',
		'truqueiro',
		'tubista',
		'tutor',
	);
	$occupations['u'] = array(
		'ufologista',
		'urbanista',
		'urologista',
	);
	$occupations['v'] = array(
		'vaqueiro',
		'varejista',
		'varredor',
		'veleiro',
		'vendedor',
		'ventríloquo',
		'verbeteiro',
		'verbetista',
		'vereador',
		'veterinário',
		'vexilógrafo',
		'vidraceiro',
		'vidreiro',
		'vigilante',
		'violinista',
		'violoncelista',
		'viticultor',
		'vitrinista',
	);
	$occupations['x'] = array(
		'xerife',
		'xilógrafo',
	);
	$occupations['w'] = array(
		'web designer',
		'web master',
	);
	$occupations['z'] = array(
		'zabumbeiro',
		'zagueiro',
		'zelador',
		'zoogeógrafo',
		'zoólogo',
		'zootecnista',
	);
	
	if( $format == 'list' ){
		$ocps = array();
		foreach( $occupations as $letter => $ocp ){
			$count = count($ocp) * (15 / 100);
			$ocps[$letter] = array_slice($ocp, 0, round($count));
		}
		return $ocps;
	}
	elseif( $format == 'unique' ){
		$letter = array_rand($occupations);
		$ocp = array_rand($occupations[$letter]);
		return $occupations[$letter][$ocp];
	}
}

function load_companies(){
	$companies = array(
		'3M',
		'524 Particip',
		'Abbott Laboratories',
		'Abril Educa',
		'Aço Altona',
		'AES Elpa',
		'AES Tiete',
		'Afluente',
		'Agconcessoes',
		'Agpart',
		'Agrenco Limited',
		'Alfa Consorcio',
		'Alfa Financeira',
		'Alfa Holding',
		'Alfa Investimentos',
		'Aliansce',
		'Aliperti',
		'All Norte',
		'All Ore',
		'Alpargatas',
		'Alupar Investimento',
		'Amazon',
		'Amazonia',
		'Ambev SA',
		'América Latina Logística',
		'American Exp',
		'Ampla Energia',
		'Anhanguera Educ',
		'Anima',
		'Apple',
		'Arcelor',
		'Arezzo',
		'ARTERIS',
		'Att Inc',
		'Autometal',
		'Avon',
		'Azevedo',
		'B2W Digital',
		'Bahema',
		'Banco ABC Brasil',
		'Banco do Brasil',
		'Banco Indusval',
		'Banco Patagonia',
		'Banco Pine',
		'Banco Sofisa',
		'Banese',
		'Banestes',
		'Bank America',
		'Banpara',
		'Banrisul',
		'Bardella',
		'Battistella',
		'Baumer',
		'BB Seguridade',
		'Belapart',
		'Bematech',
		'Betapart',
		'BHG',
		'Bic Banco',
		'Bic Monark',
		'Biomm',
		'Biosev',
		'BMFBovespa',
		'Boeing',
		'Bombril',
		'BOVA',
		'BR Insurance',
		'BR Malls',
		'BR Properties',
		'Bradesco',
		'Bradespar',
		'Brasil Brokers',
		'BRASILAGRO',
		'Braskem',
		'Brasmotor',
		'BRAX',
		'Brazil Pharma',
		'Brazilian Fr',
		'BRB Banco',
		'BRF SA',
		'Bristolmyers',
		'Brookfield',
		'BTGP Banco',
		'Buettner',
		'Cabinda Part',
		'Cacique',
		'Caconde Part',
		'Cafe Brasilia',
		'Caianda Part',
		'Cambuci',
		'Capitalpart',
		'Casan',
		'Caterpillar',
		'CCR',
		'CCX',
		'CEB',
		'Cedro',
		'CEEE-D',
		'Ceee-GT',
		'CEG',
		'Celesc',
		'Celgpar',
		'Celpa',
		'Celpe',
		'Celul Irani',
		'Cemar',
		'Cemat',
		'Cemepe',
		'Cemig',
		'Cepacs',
		'CESP',
		'Cetip',
		'Chevron',
		'Chiarelli',
		'Cia Hering',
		'Cia Providência',
		'Cielo',
		'CIMS',
		'Cisco',
		'Citigroup',
		'Clarion',
		'Cobrasma',
		'Coca Cola',
		'Coelba',
		'Coelce',
		'Colgate',
		'Comcast',
		'Comgas',
		'Conc Rio Ter',
		'Const A Lind',
		'Const Beter',
		'Contax',
		'Copasa',
		'Copel',
		'Cophillips',
		'Cor Ribeiro',
		'Cosan',
		'Cosan LTD',
		'Cosern',
		'Coteminas',
		'CPFL Energia',
		'CPFL Renovav',
		'CR2 Empreendimentos',
		'Cremer',
		'CSMO',
		'CSN',
		'CSU CardSyst',
		'CVC Brasil',
		'Cyre COM-CCP',
		'Cyrela',
		'D H B',
		'Dados Indisponíveis',
		'Daleth Part',
		'Dasa',
		'Daycoval',
		'Dimed',
		'Direcional',
		'Doc Imbituba',
		'Docas',
		'Dohler',
		'Dommo Empr',
		'Dow Chemical',
		'Dtcom-Direct',
		'Dufry AG',
		'Duratex',
		'Ebay',
		'Ecorodovias',
		'Elekeiroz',
		'Elektro',
		'Eletrobras',
		'Eletron',
		'Eletropar',
		'Eletropaulo',
		'EMAE',
		'Embraer',
		'Embratel',
		'Encorpar',
		'Energias BR',
		'Energisa',
		'Eneva',
		'Equatorial Energia',
		'Estacio Part',
		'Estrela',
		'Eternit',
		'ETFs',
		'Eucatex',
		'EVEN',
		'Excelsior',
		'Exxon Mobil',
		'Ez Tec',
		'Fdos de Inv. Ações',
		'Fdos de Inv. Part.',
		'Fedex Corp',
		'Fer C Atlant',
		'Fer Heringer',
		'Ferbasa',
		'Fibam',
		'Fibria',
		'Fleury',
		'Ford Motors',
		'Forjas Taurus',
		'Fras-Le',
		'Freeport',
		'Fundos Imobiliários',
		'Futuretel',
		'Gafisa',
		'Gama Part',
		'GE',
		'General Shopping',
		'Ger Paranapanema',
		'Gerdau',
		'Gerdau Met',
		'Gol',
		'Goldman Sachs',
		'Google',
		'GP INVEST',
		'GPC Part',
		'Gradiente',
		'Grazziotin',
		'Grendene',
		'Gruçai',
		'Guararapes',
		'Habitasul',
		'Haga',
		'Halliburton',
		'Harpia Part',
		'Helbor',
		'Hercules',
		'Home Depot',
		'Honeywell',
		'Hoteis Othon',
		'HP Company',
		'HRT',
		'Hypermarcas',
		'IBM',
		'IdeiasNet',
		'Iguaçu Cafe',
		'Iguatemi',
		'IMC Holdings',
		'Ind Cataguases',
		'Inds Romi',
		'Inepar',
		'Inepar Tel',
		'Intel',
		'Invepar',
		'Iochpe-Maxion',
		'Itaitinga',
		'Itaú Unibanco',
		'Itaúsa',
		'Itautec',
		'JB Duarte',
		'JBS Friboi',
		'Jereissati',
		'JHSF',
		'Joao Fortes',
		'Johnson',
		'Josapar',
		'JPMorgan',
		'Jsl',
		'Karsten',
		'Kepler Weber',
		'Klabin SA',
		'Kraft Group',
		'KROTON',
		'La Fonte Tel',
		'Laep',
		'Latam Airln',
		'Le Lis Blanc',
		'LF Tel',
		'Light',
		'Linx',
		'Litel',
		'Lix da Cunha',
		'LLX Logística',
		'Localiza',
		'Locamerica',
		'Lockheed',
		'Lojas Americanas',
		'Lojas Hering',
		'Lojas Renner',
		'Longdis',
		'LPS Brasil',
		'Lupatech',
		'Magazine Luiza',
		'Magnesita SA',
		'Mangels Inds',
		'Maori',
		'Marcopolo',
		'Marfrig',
		'Marisa',
		'MC Donalds',
		'MDiasBranco',
		'Melhor SP',
		'Mendes Jr',
		'Menezes Cort',
		'Merc Brasil',
		'Merc Financ',
		'Merc Invest',
		'Merck',
		'Met Duque',
		'Metal Iguacu',
		'Metal Leve',
		'Metalfrio',
		'Metisa',
		'MG Poliester',
		'Microsoft',
		'MILA',
		'Millenium',
		'MILLS',
		'Minasmaquina',
		'Minerva',
		'Minupar',
		'MMX Mineração',
		'MOBI',
		'Mont Aranha',
		'Morgan Stan',
		'MRS Logist',
		'MRV Engenharia',
		'Multiplan',
		'MULTIPLUS',
		'Mundial',
		'Nadir Figueiredo',
		'Natura',
		'Neoenergia',
		'Net',
		'Netflix',
		'Newtel Part',
		'Nord Brasil',
		'Nordon Met',
		'Nutriplant',
		'Oderich',
		'OdontoPrev',
		'OGX Petróleo',
		'Oi',
		'Oracle',
		'OSX Brasil',
		'Pacific Rub',
		'Panamericano',
		'Panatlantica',
		'Pão de Açúcar',
		'Par Al Bahia',
		'Paraná Banco',
		'Paranapanema',
		'PDG Realty',
		'Pet Manguinhos',
		'Petrobras',
		'Petropar',
		'Pettenati',
		'Pfizer',
		'Plascar',
		'Polpar',
		'Porto Seguro',
		'Portobello',
		'Positivo Inf',
		'Pq Tematico',
		'Pro Metalurg',
		'Procter Gamble',
		'Profarma',
		'Proman',
		'Prompt Part',
		'QGEP Participações',
		'Qualcomm',
		'Qualicorp',
		'Raia Drogasil',
		'Randon Part',
		'Recrusul',
		'Rede Energia',
		'Redentor',
		'Renar',
		'Renova',
		'Ret Part',
		'Riosulense',
		'RJCP EQUITY',
		'Rodobens',
		'Rossi Resid',
		'Sabesp',
		'Sanepar',
		'Sansuy',
		'Santander BR',
		'Santanense',
		'Sao Carlos',
		'São Martinho',
		'Saraiva Livr',
		'Sauipe',
		'Schlosser',
		'Schlumberger',
		'Schulz',
		'Seg Aliança Bahia',
		'Selectpart',
		'Senior Sol',
		'Ser Educa',
		'Sergen',
		'SLC Agrícola',
		'SMAL',
		'Smiles',
		'Sonae Sierra Brasil',
		'Sondotecnica',
		'Souza Cruz',
		'SP Turismo',
		'Springer',
		'Starbucks',
		'Sul América',
		'Sultepa',
		'Suzano Holding',
		'Suzano Papel',
		'T4F Entretenimento',
		'Taesa',
		'Tarpon Investment',
		'Tec Blumenau',
		'Tecel S Jose',
		'Technos',
		'Tecnisa',
		'Tecnosolo',
		'Tectoy',
		'Tegma',
		'Teka',
		'Tekno',
		'Telebras',
		'Telefônica Brasil',
		'Telinvest',
		'Tempo Participações',
		'Tereos',
		'Textil Renaux',
		'TIM Participações',
		'Time Warner',
		'Totvs',
		'Tractebel',
		'Trans Paulista',
		'Trevisa',
		'Trisul',
		'Triunfo Part',
		'Tupy',
		'Ultrapar',
		'Unicasa',
		'Unipar',
		'UPS',
		'Uptick',
		'Usiminas',
		'UTIP',
		'V-Agro',
		'Vale',
		'Valetron',
		'Valid',
		'Verizon',
		'Viavarejo',
		'Vigor',
		'Visa Inc',
		'Viver',
		'Vulcabras',
		'Wal Mart',
		'Walt Disney',
		'Weg',
		'Wells Fargo',
		'Wembley',
		'Wetzel',
		'Whirlpool',
		'Wilson Sons',
		'WLM',
		'Xerox Corp',
		'Zain Part',
	);
	$cp = array_rand($companies);
	return $companies[$cp];
}








