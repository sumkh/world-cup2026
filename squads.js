/* ============================================================
   squads.js — World Cup 2026 squads (all 48 teams).

   Source / credit: API-SPORTS — "FIFA World Cup 2026 Lineups:
   All Teams, Coaches and Players" (June 4, 2026).
   https://www.api-football.com/news/post/fifa-world-cup-2026-lineups-all-teams-coaches-and-players

   Team images are also from API-SPORTS (hosted locally in img/teams/).
   slug = local image filename (img/teams/<slug>.webp).
   ============================================================ */
window.WC_SQUADS = [
  { name:"Germany", slug:"germany",
    gk:["Manuel Neuer","Oliver Baumann","Alexander Nübel"],
    df:["Antonio Rüdiger","Waldemar Anton","Jonathan Tah","Joshua Kimmich","Nico Schlotterbeck","Nathaniel Brown","David Raum","Malick Thiaw"],
    mf:["Aleksandar Pavlović","Leon Goretzka","Jamie Leweling","Jamal Musiala","Pascal Groß","Angelo Stiller","Florian Wirtz","Leroy Sané","Nadiem Amiri","Felix Nmecha","Lennart Karl"],
    fw:["Kai Havertz","Nick Woltemade","Maximilian Beier","Deniz Undav"] },

  { name:"England", slug:"england",
    gk:["Jordan Pickford","Dean Henderson","James Trafford"],
    df:["Ezri Konsa","Nico O'Reilly","John Stones","Marc Guéhi","Valentino Livramento","Daniel Burn","Reece James","Djed Spence","Jarell Quansah"],
    mf:["Declan Rice","Elliot Anderson","Jude Bellingham","Jordan Henderson","Kobbie Mainoo","Morgan Rogers","Eberechi Eze"],
    fw:["Bukayo Saka","Harry Kane","Marcus Rashford","Anthony Gordon","Oliver Watkins","Noni Madueke","Ivan Toney"] },

  { name:"Austria", slug:"austria",
    gk:["Alexander Schlager","Florian Wiegele","Patrick Pentz"],
    df:["David Affengruber","Kevin Danso","Stefan Posch","David Alaba","Philipp Lienhart","Phillip Mwene","Marco Friedl","Michael Svoboda"],
    mf:["Xaver Schlager","Nicolas Seiwald","Marcel Sabitzer","Florian Grillitsch","Carney Chukwuemeka","Romano Schmid","Christoph Baumgartner","Konrad Laimer","Alexander Prass","Paul Wanner","Alessandro Schöpf"],
    fw:["Marko Arnautović","Michael Gregoritsch","Saša Kalajdžić","Patrick Wimmer"] },

  { name:"Belgium", slug:"belgium",
    gk:["Thibaut Courtois","Senne Lammens","Mike Penders"],
    df:["Zeno Debast","Arthur Theate","Brandon Mechele","Maxim De Cuyper","Thomas Meunier","Koni De Winter","Joaquin Seys","Timothy Castagne","Nathan Ngoy"],
    mf:["Axel Witsel","Kevin De Bruyne","Youri Tielemans","Diego Moreira","Hans Vanaken","Alexis Saelemaekers","Nicolas Raskin","Amadou Onana"],
    fw:["Romelu Lukaku","Leandro Trossard","Jérémy Doku","Dodi Lukebakio","Charles De Ketelaere","Matías Fernández-Pardo"] },

  { name:"Bosnia & Herzegovina", slug:"bosnia",
    gk:["Nikola Vasilj","Mladen Jurkas","Martin Zlomislić"],
    df:["Nihad Mujakić","Dennis Hadžikadunic","Tarik Muharemović","Sead Kolašinac","Amar Dedić","Nikola Katić","Stjepan Radeljić","Nidal Čelik"],
    mf:["Benjamin Tahirović","Armin Gigović","Ivan Bašić","Ivan Šunjić","Amar Memić","Amir Hadžiahmetović","Dženis Burnić","Ermin Mahmić"],
    fw:["Samed Bazdar","Ermedin Demirović","Edin Džeko","Kerim Alajbegović","Esmir Bajraktarević","Haris Tabaković","Jovo Lukić"] },

  { name:"Croatia", slug:"croatia",
    gk:["Dominik Livaković","Ivor Pandur","Dominik Kotarski"],
    df:["Josip Stanišić","Marin Pongračić","Joško Gvardiol","Duje Čaleta-Car","Josip Šutalo","Kristijan Jakić","Luka Vušković","Martin Erlić"],
    mf:["Nikola Moro","Mateo Kovačić","Luka Modrić","Nikola Vlašić","Mario Pašalić","Martin Baturina","Petar Sučić","Toni Fruk","Luka Sučić"],
    fw:["Andrej Kramarić","Ante Budimir","Ivan Perišić","Igor Matanović","Marco Pašalić","Petar Musa"] },

  { name:"Scotland", slug:"scotland",
    gk:["Angus Gunn","Liam Kelly","Craig Gordon"],
    df:["Aaron Hickey","Andy Robertson","Grant Hanley","Kieran Tierney","Jack Hendry","John Souttar","Dominic Hyam","Nathan Patterson","Anthony Ralston","Scott McKenna"],
    mf:["Scott McTominay","John McGinn","Tyler Fletcher","Ryan Christie","Lewis Ferguson","Kenny McLean"],
    fw:["Lyndon Dykes","Che Adams","Ross Stewart","Ben Gannon-Doak","George Hirst","Lawrence Shankland","Findlay Curtis"] },

  { name:"Spain", slug:"spain",
    gk:["David Raya","Joan García","Unai Simón"],
    df:["Marc Pubill","Álex Grimaldo","Eric García","Marcos Llorente","Pedro Porro","Aymeric Laporte","Pau Cubarsí","Marc Cucurella"],
    mf:["Mikel Merino","Fabián Ruiz","Pablo Gavira","Álex Baena","Rodrigo Hernández","Martín Zubimendi","Pedro López"],
    fw:["Ferran Torres","Dani Olmo","Yeremy Pino","Nico Williams","Lamine Yamal","Mikel Oyarzabal","Víctor Muñoz","Borja Iglesias"] },

  { name:"France", slug:"france",
    gk:["Brice Samba","Mike Maignan","Robin Risser"],
    df:["Malo Gusto","Lucas Digne","Dayot Upamecano","Jules Koundé","Ibrahima Konaté","William Saliba","Théo Hernandez","Lucas Hernandez","Maxence Lacroix"],
    mf:["Manu Koné","Aurélien Tchouaméni","N'Golo Kanté","Adrien Rabiot","Warren Zaïre-Emery","Rayan Cherki","Maghnes Akliouche"],
    fw:["Ousmane Dembélé","Marcus Thuram","Kylian Mbappé","Michaël Olise","Bradley Barcola","Désiré Doué","Jean-Philippe Mateta"] },

  { name:"Norway", slug:"norway",
    gk:["Ørjan Nyland","Sander Tangvik","Egil Selvik"],
    df:["Kristoffer Ajer","Leo Østigård","David Wolfe","Fredrik Bjørkan","Marcus Pedersen","Torbjørn Heggem","Sondre Langas","Henrik Falchener"],
    mf:["Morten Thorsby","Patrick Berg","Sander Berge","Martin Ødegaard","Fredrik Aursnes","Kristian Thorstvedt","Thelo Aasgaard","Andreas Schjelderup","Oscar Bobb","Jens Hauge"],
    fw:["Alexander Sørloth","Erling Haaland","Jørgen Larsen","Antonio Nusa","Julian Ryerson"] },

  { name:"Netherlands", slug:"netherlands",
    gk:["Bart Verbruggen","Robin Roefs","Mark Flekken"],
    df:["Jurriën Timber","Virgil van Dijk","Nathan Aké","Jan-Paul van Hecke","Mats Wieffer","Micky van de Ven","Denzel Dumfries","Jorrel Hato"],
    mf:["Marten de Roon","Justin Kluivert","Ryan Gravenberch","Tijjani Reijnders","Guus Til","Teun Koopmeiners","Frenkie de Jong","Quinten Timber"],
    fw:["Wout Weghorst","Memphis Depay","Cody Gakpo","Noa Lang","Donyell Malen","Brian Brobbey","Crysencio Summerville"] },

  { name:"Portugal", slug:"portugal",
    gk:["Diogo Costa","José Sá","Rui Silva"],
    df:["Nelson Semedo","Rúben Dias","Tomás Araújo","Diogo Dalot","Renato Veiga","Gonçalo Inácio","João Cancelo","Samu Costa","Nuno Mendes"],
    mf:["Matheus Nunes","Bruno Fernandes","Bernardo Silva","João Neves","Rúben Neves","Vítor Ferreira"],
    fw:["Cristiano Ronaldo","Gonçalo Ramos","João Félix","Francisco Trincão","Rafael Leão","Pedro Neto","Gonçalo Guedes","Francisco Conceição"] },

  { name:"Sweden", slug:"sweden",
    gk:["Jacob Zetterström","Viktor Johansson","Kristoffer Nordfeldt"],
    df:["Gustaf Lagerbielke","Victor Lindelöf","Isak Hien","Gabriel Gudmundsson","Herman Johansson","Daniel Svensson","Hjalmar Ekdal","Carl Starfelt","Eric Smith","Alexander Bernhardsson","Elliot Stroud"],
    mf:["Lucas Bergvall","Benjamin Nygren","Ken Sema","Jesper Karlström","Yasin Ayari","Mattias Svanberg","Besfort Zeneli"],
    fw:["Alexander Isak","Anthony Elanga","Viktor Gyökeres","Gustaf Nilsson","Taha Ali"] },

  { name:"Switzerland", slug:"switzerland",
    gk:["Gregor Kobel","Yvon Mvogo","Marvin Keller"],
    df:["Miro Muheim","Silvan Widmer","Nico Elvedi","Manuel Akanji","Ricardo Rodríguez","Eray Cömert","Aurèle Amenda","Luca Jaquez"],
    mf:["Denis Zakaria","Remo Freuler","Johan Manzambi","Granit Xhaka","Ardon Jashari","Djibril Sow","Michel Aebischer","Fabian Rieder"],
    fw:["Breel Embolo","Dan Ndoye","Christian Fassnacht","Rubén Vargas","Noah Okafor","Zeki Amdouni","Cédric Itten"] },

  { name:"Czechia", slug:"czechia",
    gk:["Matěj Kovář","Jindřich Stánek","Lukáš Horníček"],
    df:["David Zima","Tomáš Holeš","Robin Hranáč","Vladimír Coufal","Štěpán Chaloupek","Ladislav Krejčí","David Jurásek","Jaroslav Zelený","David Doudera"],
    mf:["Vladimír Darida","Lukáš Červ","Lukáš Provod","Michal Sadílek","Tomáš Souček","Alexandr Sojka","Hugo Šochůrek"],
    fw:["Adam Hložek","Patrik Schick","Jan Kuchta","Mojmír Chytil","Pavel Šulc","Tomáš Chorý","Denis Višinský"] },

  { name:"Turkey", slug:"turkey",
    gk:["Mert Günok","Altay Bayındır","Uğurcan Çakır"],
    df:["Zeki Çelik","Merih Demiral","Çağlar Söyüncü","Eren Elmalı","Abdülkerim Bardakçı","Ozan Kabak","Mert Müldür","Ferdi Kadıoğlu","Samet Akaydın"],
    mf:["Salih Özcan","Orkun Kökcü","Hakan Çalhanoğlu","İsmail Yüksek","Kaan Ayhan"],
    fw:["Kerem Aktürkoğlu","Arda Güler","Deniz Gül","Kenan Yıldız","İrfan Kahveci","Yunus Akgün","Barış Yılmaz","Oğuz Aydın","Can Uzun"] },

  { name:"Argentina", slug:"argentina",
    gk:["Juan Musso","Gerónimo Rulli","Emiliano Martínez"],
    df:["Leonardo Balerdi","Nicolás Tagliafico","Gonzalo Montiel","Lisandro Martínez","Cristian Romero","Nicolás Otamendi","Facundo Medina","Nahuel Molina"],
    mf:["Leandro Paredes","Rodrigo de Paul","Valentín Barco","Giovani Lo Celso","Exequiel Palacios","Nicolás González","Alexis Mac Allister","Enzo Fernández"],
    fw:["Julián Álvarez","Lionel Messi","Thiago Almada","Giuliano Simeone","Nicolás Paz","José López","Lautaro Martínez"] },

  { name:"Brazil", slug:"brazil",
    gk:["Alisson Becker","Weverton Caldeira","Ederson Moraes"],
    df:["Wesley","Gabriel Magalhães","Marcos Corrêa","Alex Sandro","Danilo Luiz","Bremer","Léo Pereira","Douglas Santos","Roger Ibanez"],
    mf:["Carlos Casimiro","Bruno Guimarães","Fábio Tavares","Danilo Santos","Lucas Paquetá"],
    fw:["Vinícius Júnior","Matheus Cunha","Neymar Santos","Raphael Belloli","Endrick Sousa","Luiz Henrique","Gabriel Martinelli","Igor Thiago","Rayan"] },

  { name:"Colombia", slug:"colombia",
    gk:["David Ospina","Camilo Vargas","Álvaro Montero"],
    df:["Daniel Muñoz","Jhon Lucumí","Santiago Arias","Yerry Mina","Gustavo Puerta","Johan Mojica","Willer Ditta","Deiver Machado","Dávinson Sánchez"],
    mf:["Kevin Castaño","Richard Ríos","Jorge Carrascal","James Rodríguez","Jhon Arias","Juan Portilla","Jefferson Lerma","Juan Quintero"],
    fw:["Luis Díaz","Jhon Córdoba","Juan Hernández","Leandro Campaz","Luis Suárez","Andrés Gómez"] },

  { name:"Ecuador", slug:"ecuador",
    gk:["Hernán Galíndez","Moisés Ramírez","Gonzalo Valle"],
    df:["Félix Torres","Piero Hincapié","Joel Ordóñez","Willian Pacho","Pervis Estupiñán","Ángelo Preciado","Jackson Porozo","Yaimar Medina"],
    mf:["Jordy Alcívar","Anthony Valencia","Kendry Páez","Alan Minda","Pedro Vite","Denil Castillo","Alan Franco","Moisés Caicedo"],
    fw:["John Yeboah","Kevin Rodríguez","Enner Valencia","Jordy Caicedo","Gonzalo Plata","Nilson Angulo","Jeremy Arévalo"] },

  { name:"Paraguay", slug:"paraguay",
    gk:["Roberto Fernández","Orlando Gill","Gastón Olveira"],
    df:["Gustavo Velázquez","Omar Alderete","Juan Cáceres","Fabián Balbuena","Junior Alonso","José Canale","Gustavo Gómez","Alexandro Maidana"],
    mf:["Ramón Sosa","Diego Gómez","Miguel Almirón","Mauricio","Andrés Cubas","Damián Bobadilla","Braian Ojeda","Matías Galarza","Gustavo Caballero"],
    fw:["Antonio Sanabria","Alejandro Romero","Álex Arce","Julio Enciso","Gabriel Ávalos","Isidro Pitta"] },

  { name:"Uruguay", slug:"uruguay",
    gk:["Sergio Rochet","Santiago Mele","Fernando Muslera"],
    df:["José Giménez","Sebastián Cáceres","Ronald Araújo","Guillermo Varela","Mathías Olivera","Matías Viña","Santiago Bueno"],
    mf:["Manuel Ugarte","Rodrigo Bentancur","Nicolás de la Cruz","Federico Valverde","Giorgian de Arrascaeta","Agustín Canobbio","Emiliano Martínez","Maximiliano Araújo","Joaquín Piquerez","Juan Sanabria","Rodrigo Zalazar"],
    fw:["Darwin Núñez","Facundo Pellistri","Brian Rodríguez","Rodrigo Aguirre","Federico Viñas"] },

  { name:"Canada", slug:"canada",
    gk:["Dayne St. Clair","Maxime Crépeau","Owen Goodman"],
    df:["Alistair Johnston","Alfie Jones","Luc de Fougerolles","Joel Waterman","Derek Cornelius","Moïse Bombito","Alphonso Davies","Richie Laryea","Niko Sigur"],
    mf:["Mathieu Choinière","Stephen Eustaquio","Ismaël Koné","Liam Millar","Jacob Shaffelburg","Jonathan Osorio","Nathan Saliba","Marcelo Flores"],
    fw:["Cyle Larin","Jonathan David","Tani Oluwaseyi","Tajon Buchanan","Ali Ahmed","Promise David"] },

  { name:"United States", slug:"united-states",
    gk:["Matt Turner","Matt Freese","Chris Brady"],
    df:["Sergiño Dest","Chris Richards","Antonee Robinson","Auston Trusty","Miles Robinson","Tim Ream","Alex Freeman","Max Arfsten","Mark McKenzie","Joe Scally"],
    mf:["Tyler Adams","Giovanni Reyna","Weston McKennie","Sebastian Berhalter","Cristian Roldán","Malik Tillman"],
    fw:["Ricardo Pepi","Christian Pulisic","Brenden Aaronson","Haji Wright","Folarin Balogun","Timothy Weah","Alex Zendejas"] },

  { name:"Mexico", slug:"mexico",
    gk:["Raúl Rangel","Carlos Acevedo","Guillermo Ochoa"],
    df:["Jorge Sánchez","César Montes","Edson Álvarez","Johan Vásquez","Israel Reyes","Mateo Chávez","Jesús Gallardo"],
    mf:["Erik Lira","Luis Romo","Álvaro Fidalgo","Orbelín Piñeda","Obed Vargas","Gilberto Mora","Luis Chávez","Brian Gutiérrez"],
    fw:["Raúl Jiménez","Alexis Vega","Santiago Giménez","Armando González","Julián Quiñones","César Huerta","Guillermo Martínez","Roberto Alvarado"] },

  { name:"Curaçao", slug:"curacao",
    gk:["Eloy Room","Tyrick Bodak","Trevor Doornbusch"],
    df:["Shurandy Sambo","Jurien Gaari","Roshon van Eijma","Sherel Floranus","Armando Obispo","Joshua Brenet","Riechedly Bazoer","Deveron Fonville"],
    mf:["Godfried Roemeratoe","Juninho Bacuna","Livano Comenencia","Leandro Bacuna","Arjany Martha","Tahith Chong","Kevin Felida"],
    fw:["Jürgen Locadia","Jeremy Antonisse","Sontje Hansen","Tyrese Noslin","Kenji Gorre","Jearl Margaritha","Brandley Kuwas","Gervane Kastaneer"] },

  { name:"Haiti", slug:"haiti",
    gk:["Johny Placide","Alexandre Pierre","Josué Duverger"],
    df:["Carlens Arcus","Keeto Thermoncy","Ricardo Ade","Hannes Delcroix","Martin Expérience","Markhus Lacroix","Jean-Kevin Duverne","Wilguens Paugain"],
    mf:["Carl Sainte","Jean-Ricner Bellegarde","Leverton Pierre","Danley Jean Jacques","Dominique Simon","Woodensky Pierre"],
    fw:["Derrick Etienne","Duckens Nazon","Louicius Deedson","Ruben Providence","Lenny Joseph","Wilson Isidor","Yassin Fortune","Frantzdy Pierrot"] },

  { name:"Panama", slug:"panama",
    gk:["Luis Mejía","César Samudio","Orlando Mosquera"],
    df:["César Blackman","José Córdoba","Fidel Escobar","Edgardo Farina","Jiovany Ramos","Carlos Harvey","Eric Davis","Andrés Andrade","Amir Murillo","Roderick Miller","Jorge Gutiérrez"],
    mf:["Cristian Martínez","José Rodríguez","Adalberto Carrasquilla","Ismael Díaz","Edgar Bárcenas","Alberto Quintero","Aníbal Godoy","César Yanis"],
    fw:["Tomás Rodríguez","José Fajardo","Cecilio Waterman","Azarías Londoño"] },

  { name:"South Africa", slug:"south-africa",
    gk:["Ronwen Williams","Sipho Chaine","Ricardo Goss"],
    df:["Thabang Matuludi","Khulumani Ndamane","Aubrey Modiba","Mbekezeli Mbokazi","Samukelo Kabini","Nkosinathi Sibisi","Khuliso Mudau","Ime Okon","Olwethu Makhanya","Bradley Cross"],
    mf:["Teboho Mokoena","Thalente Mbatha","Themba Zwane","Sphephelo Sithole","Jayden Adams"],
    fw:["Oswin Appollis","Tshepang Moremi","Lyle Foster","Relebohile Mofokeng","Thapelo Maseko","Iqraam Rayners","Evidence Makgopa","Kamogelo Sebelebele"] },

  { name:"Algeria", slug:"algeria",
    gk:["Melvin Mastil","Oussama Benbot","Luca Zidane"],
    df:["Aïssa Mandi","Achraf Abada","Mohamed Tougaï","Zineddine Belaïd","Jaouen Hadjam","Rayan Aït-Nouri","Rafik Belghali","Ramy Bensebaini","Samir Chergui"],
    mf:["Ramiz Zerrouki","Houssem Aouar","Fares Chaïbi","Hicham Boudaoui","Nabil Bentaleb","Ibrahim Maza","Yassine Titraoui"],
    fw:["Riyad Mahrez","Amine Gouiri","Anis Hadj Moussa","Nadhir Benbouali","Mohamed Amoura","Adil Boulbina","Fares Ghedjemis"] },

  { name:"Cape Verde", slug:"cape-verde",
    gk:["Vozinha","Márcio Rosa","CJ Dos Santos"],
    df:["Stopira","Diney Borges","Pico Lopes","Logan Costa","Sidny Cabral","Steven Moreira","Wagner Pina","Kelvin Pires"],
    mf:["Kevin Pina","Jovane Cabral","João Paulo","Jamiro Monteiro","Garry Rodrigues","Deroy Duarte","Laros Duarte","Yannick Semedo","Willy Semedo","Telmo Arcanjo","Nuno da Costa","Hélio Varela"],
    fw:["Gilson Benchimol","Dailon Livramento","Ryan Mendes"] },

  { name:"Ivory Coast", slug:"ivory-coast",
    gk:["Yahia Fofana","Mohamed Koné","Alban Lafont"],
    df:["Ousmane Diomandé","Ghislain Konan","Wilfried Singo","Odilon Kossounou","Christopher Operi","Guela Doué","Emmanuel Agbadou","Evan Ndicka"],
    mf:["Jean Séri","Seko Fofana","Franck Kessié","Ibrahim Sangaré","Parfait Guiagon","Christ Oulai"],
    fw:["Ange-Yoan Bonny","Simon Adingra","Yan Diomandé","Elye Wahi","Oumar Diakité","Amad Diallo","Nicolas Pépé","Evann Guessand","Bazoumana Touré"] },

  { name:"Egypt", slug:"egypt",
    gk:["Mohamed Elshenawy","Mahdy Soliman","Mostafa Shoubir","Mohamed Alaa"],
    df:["Yasser Ibrahim","Mohamed Hany","Hossam Abdelmaguid","Ramy Rabia","Mohamed Abdelmoneim","Ahmed Fatouh","Karim Hafez","Tarek Alaa"],
    mf:["Emam Ashour","Mostafa Zico","Hamdy Fathy","Mohanad Lashin","Nabil Donga","Marawan Attia","Mahmoud Saber"],
    fw:["Mahmoud Hassan","Hamza Abdelkarim","Mohamed Salah","Haissem Hassan","Ibrahim Adel","Omar Marmoush","Mahmoud Hamdy"] },

  { name:"Ghana", slug:"ghana",
    gk:["Lawrence Zigi","Joseph Anang","Benjamin Asare"],
    df:["Alidu Seidu","Jonas Adjetey","Abdul Mumin","Gideon Mensah","Baba Rahman","Jerome Opoku","Kojo Oppong","Derrick Luckassen","Marvin Senaya"],
    mf:["Caleb Yirenkyi","Thomas Partey","Kwasi Sibo","Antoine Semenyo","Elisha Owusu","Augustine Boakye"],
    fw:["Fatawu Issahaku","Jordan Ayew","Brandon Thomas-Asante","Christopher Baah","Iñaki Williams","Kamaldeen Sulemana","Ernest Nuamah","Prince Adu"] },

  { name:"Morocco", slug:"morocco",
    gk:["Yassine Bounou","Munir El Kajoui","Ahmed Tagnaouti"],
    df:["Achraf Hakimi","Noussair Mazraoui","Nayef Aguerd","Zakaria El Ouahdi","Issa Diop","Chadi Riad","Youssef Belammari","Redouane Halhal","Anass Salah-Eddine"],
    mf:["Sofyan Amrabat","Ayyoub Bouaddi","Chemsdine Talbi","Azzedine Ounahi","Ismaël Saibari","Samir El Mourabet","Gessime Yassine","Bilal El Khannouss","Neil El Aynaoui"],
    fw:["Soufiane Rahimi","Brahim Díaz","Abde Ezzalzouli","Ayoub El Kaabi","Ayoub Amaimouni"] },

  { name:"DR Congo", slug:"dr-congo",
    gk:["Lionel Mpasi","Timothy Fayulu","Matthieu Epolo"],
    df:["Aaron Wan-Bissaka","Steve Kapuadi","Axel Tuanzebe","Dylan Batubinsika","Joris Kayembe","Chancel Mbemba","Gédéon Kalulu","Arthur Masuaku"],
    mf:["Ngalayel Mukau","Nathanaël Mbuku","Samuel Moutoussamy","Théo Bongonda","Noah Sadiki","Aaron Tshibola","Charles Pickel","Edo Kayembe"],
    fw:["Brian Cipenga","Gaël Kakuta","Meschack Elia","Cédric Bakambu","Fiston Mayele","Yoane Wissa","Simon Banza"] },

  { name:"Senegal", slug:"senegal",
    gk:["Yehvann Diouf","Édouard Mendy","Mory Diaw"],
    df:["Mamadou Sarr","Kalidou Koulibaly","Abdoulaye Seck","Ismail Jakobs","Krepin Diatta","Moussa Niakhaté","Antoine Mendy","El Hadji Diouf"],
    mf:["Idrissa Gueye","Pathé Ciss","Lamine Camara","Pape Sarr","Habib Diarra","Bara Ndiaye","Pape Gueye"],
    fw:["Assane Diao","Bamba Dieng","Sadio Mané","Nicolas Jackson","Chérif Ndiaye","Iliman Ndiaye","Ismaïla Sarr","Ibrahim Mbaye"] },

  { name:"Tunisia", slug:"tunisia",
    gk:["Mouhib Chamakh","Aymen Dahmen","Sabri Ben Hessen"],
    df:["Ali Abdi","Montassar Talbi","Omar Rekik","Adam Arous","Dylan Bronn","Mortadha Ben Ouanes","Yan Valery","Mohamed Ben Hmida","Moutaz Neffati","Raed Chikhaoui"],
    mf:["Hannibal Mejbri","Ismaël Gharbi","Rani Khedira","Khalil Ayari","Mohamed Hadj Mahmoud","Ellyes Skhiri","Anis Slimane","Sebastian Tounekti"],
    fw:["Elias Achouri","Elias Saad","Hazem Mastouri","Rayan Elloumi","Firas Chaouat"] },

  { name:"Saudi Arabia", slug:"saudi-arabia",
    gk:["Nawaf Al-Aqidi","Mohammed Al-Owais","Ahmed Al-Kassar"],
    df:["Ali Majrashi","Ali Lajami","Abdulelah Al-Amri","Hassan Al-Tambakti","Saud Abdulhamid","Nawaf Bu Washl","Hassan Kadish","Moteb Al-Harbi","Jehad Thikri","Mohammed Abu Alshamat"],
    mf:["Nasser Al-Dawsari","Musab Al-Juwayr","Abdullah Al-Khaibari","Ziyad Al-Johani","Ala Al-Hajji","Mohamed Kanno"],
    fw:["Aiman Yahya","Feras Al-Brikan","Salem Al-Dawsari","Saleh Al-Shehri","Khalid Al-Ghannam","Abdullah Al-Hamddan","Sultan Mandash"] },

  { name:"Australia", slug:"australia",
    gk:["Mathew Ryan","Paul Izzo","Patrick Beach"],
    df:["Miloš Degenek","Alessandro Circati","Jacob Italiano","Jordan Bos","Jason Geria","Kai Trewin","Aziz Behich","Harry Souttar","Cameron Burgess","Lucas Herrington"],
    mf:["Connor Metcalfe","Aiden O'Neill","Cameron Devlin","Jackson Irvine","Paul Okon-Engstler"],
    fw:["Mathew Leckie","Mohamed Touré","Ajdin Hrustić","Awer Mabil","Nestory Irankunda","Cristian Volpato","Nishan Velupillay","Tete Yengi"] },

  { name:"Iraq", slug:"iraq",
    gk:["Fahad Talib","Jalal Hassan","Ahmed Basil"],
    df:["Rebin Ghareeb","Hussein Ali","Zaid Tahseen","Akam Hashim","Munaf Younus","Ahmed Yahya","Merchas Doski","Mustafa Saadoon","Frans Putros"],
    mf:["Youssef Amyn","Ibrahim Bayesh","Zidane Iqbal","Amir Al-Ammari","Kevin Yakob","Aimar Sher","Zaid Ismael"],
    fw:["Ali Al-Hamadi","Mohanad Ali","Ahmed Qasim","Ali Yousif","Ali Jasim","Aymen Hussein","Marko Farji"] },

  { name:"Japan", slug:"japan",
    gk:["Zion Suzuki","Keisuke Osako","Tomoki Hayakawa"],
    df:["Yukinari Sugawara","Shogo Taniguchi","Kou Itakura","Yuto Nagatomo","Tsuyoshi Watanabe","Ayumu Seko","Hiroki Ito","Takehiro Tomiyasu","Junnosuke Suzuki"],
    mf:["Wataru Endo","Ao Tanaka","Takefusa Kubo","Ritsu Doan","Daizen Maeda","Keito Nakamura","Junya Ito","Daichi Kamada","Yuito Suzuki","Kaishu Sano"],
    fw:["Keisuke Goto","Ayase Ueda","Koki Ogawa","Kento Shiogai"] },

  { name:"Jordan", slug:"jordan",
    gk:["Yazeed Abu Laila","Nour Baniateyah","Abdallah Al-Fakhori"],
    df:["Mohammad Abu Hasheesh","Abdallah Nasib","Husam Abu Dahab","Yazan Al-Arab","Mohammad Abu Al-Nadi","Saleem Obaid","Saed Al-Rosan","Ehsan Haddad","Anas Badawi"],
    mf:["Amer Jamous","Noor Al-Rawabdeh","Rajaei Ayed","Ibrahim Sadeh","Mohannad Abu Taha","Nizar Al-Rashdan","Mohammad Al-Daoud"],
    fw:["Mohammad Abu Zraiq","Ali Olwan","Mousa Al-Tamari","Odeh Fakhoury","Mahmoud Al-Mardi","Ibrahim Sabra","Ali Azaizeh"] },

  { name:"Uzbekistan", slug:"uzbekistan",
    gk:["Utkir Yusupov","Abduvohid Nematov","Botirali Ergashev"],
    df:["Abdukodir Khusanov","Khojiakbar Alijonov","Farrukh Sayfiev","Rustam Ashurmatov","Sherzod Nasrullaev","Umar Eshmurodov","Abdulla Abdullaev","Behruzjon Karimov","Avazbek Ulmasaliyev","Jakhongir Urozov"],
    mf:["Akmal Mozgovoy","Otabek Shukurov","Jamshid Iskanderov","Odiljon Xamrobekov","Jaloliddin Masharipov","Oston Urunov","Dostonbek Khamdamov","Azizjon Ganiev","Abbosbek Fayzullaev","Sherzod Esanov"],
    fw:["Eldor Shomurodov","Azizbek Amonov","Igor Sergeev"] },

  { name:"Qatar", slug:"qatar",
    gk:["Mahmoud Abu Nada","Salah Zakaria","Meshaal Barsham"],
    df:["Pedro Miguel","Lucas Mendes","Issa Laye","Jassem Gaber","Ayoub Aloui","Homam Ahmed","Boualem Khoukhi","Sultan Al-Brake","Al-Hashmi Al-Hussein"],
    mf:["Abdulaziz Hatem","Karim Boudiaf","Ahmed Al-Ganehi","Ahmed Fathy","Assim Madibo"],
    fw:["Ahmed Alaaeldin","Edmilson Júnior","Mohammed Muntari","Hassan Al-Haydos","Akram Afif","Yusuf Abdurisag","Almoez Ali","Tahsin Jamshid","Mohamed Manai"] },

  { name:"South Korea", slug:"south-korea",
    gk:["Seunggyu Kim","Bumkeun Song","Hyeonwoo Jo"],
    df:["Hanbeom Lee","Minjae Kim","Taehyeon Kim","Taeseok Lee","Wije Cho","Moonhwan Kim","Jinseob Park","Youngwoo Seol","Jens Castrop"],
    mf:["Gihyuk Lee","Inbeom Hwang","Seungho Paik","Jaesung Lee","Heechan Hwang","Junho Bae","Kangin Lee","Hyunjun Yang","Jingyu Kim","Jisung Eom","Donggyeong Lee"],
    fw:["Heungmin Son","Guesung Cho","Hyeongyu Oh"] },

  { name:"Iran", slug:"iran",
    gk:["Alireza Beiranvand","Payam Niazmand","Hossein Hosseini"],
    df:["Saleh Hardani","Ehsan Hajisafi","Shoja Khalilzadeh","Milad Mohammadi","Hossein Kanani","Arya Yousefi","Ali Nemati","Ramin Rezaeian","Danial Iri"],
    mf:["Saeid Ezatolahi","Alireza Jahanbakhsh","Mohammad Mohebbi","Saman Ghoddos","Roozbeh Cheshmi","Mehdi Torabi","Mohammad Ghorbani","Amirmohammad Razaghinia"],
    fw:["Mehdi Taremi","Mehdi Ghayedi","Ali Alipour","Amirhossein Hosseinzadeh","Shahriyar Moghanloo","Dennis Dargahi"] },

  { name:"New Zealand", slug:"new-zealand",
    gk:["Max Crocombe","Alex Paulsen","Michael Woud"],
    df:["Tim Payne","Francis de Vries","Tyler Bindon","Michael Boxall","Liberato Cacace","Nando Pijnaker","Finn Surman","Callan Elliot","Tommy Smith"],
    mf:["Joe Bell","Matthew Garbett","Marko Stamenić","Sarpreet Singh","Elijah Just","Alex Rufer","Ben Old","Callum McCowatt","Ryan Thomas","Lachlan Bayliss"],
    fw:["Chris Wood","Kosta Barbarouses","Ben Waine","Jesse Randall"] }
];
