<?php

namespace App\Services\Card;

/**
 * Built-in Pakistan division / city / shop-type / golden serial catalog.
 */
class SolqamPakistanGeoSeed
{
    /**
     * @return list<array{0:string,1:string,2:string,3:list<array{0:string,1:string}>}>
     */
    public static function divisions(): array
    {
        return [
            ['01', 'Lahore Division', 'Punjab', [
                ['01', 'Lahore'], ['02', 'Kasur'], ['03', 'Sheikhupura'], ['04', 'Nankana Sahib'],
                ['05', 'Pattoki'], ['06', 'Chunian'], ['07', 'Kot Radha Kishan'], ['08', 'Muridke'],
                ['09', 'Ferozewala'], ['10', 'Sharqpur Sharif'], ['11', 'Sangla Hill'], ['12', 'Shahkot'],
                ['13', 'Raiwind'], ['14', 'Phoolnagar'], ['15', 'Bhai Pheru'], ['16', 'Farooqabad'],
                ['17', 'Narang Mandi'], ['18', 'Safdarabad'], ['19', 'Kanganpur'], ['20', 'Mandi Faizabad'],
                ['21', 'Kahna Nau'], ['22', 'Wagah'], ['23', 'Manga Mandi'], ['24', 'Kot Abdul Malik'],
            ]],
            ['02', 'Sahiwal Division', 'Punjab', [
                ['01', 'Sahiwal'], ['02', 'Okara'], ['03', 'Pakpattan'],
                ['04', 'Depalpur'], ['05', 'Renala Khurd'], ['06', 'Arifwala'], ['07', 'Chichawatni'],
                ['08', 'Haveli Lakha'], ['09', 'Basirpur'], ['10', 'Qabula'], ['11', 'Harappa'],
                ['12', 'Gogera'], ['13', 'Hujra Shah Muqeem'],
            ]],
            ['03', 'Gujranwala Division', 'Punjab', [
                ['01', 'Gujranwala'], ['02', 'Sialkot'], ['03', 'Gujrat'], ['04', 'Narowal'], ['05', 'Hafizabad'], ['06', 'Mandi Bahauddin'],
                ['07', 'Kamoke'], ['08', 'Daska'], ['09', 'Sambrial'], ['10', 'Pasrur'],
                ['11', 'Wazirabad'], ['12', 'Nowshera Virkan'], ['13', 'Qila Didar Singh'], ['14', 'Ghakhar Mandi'],
                ['15', 'Alipur Chattha'], ['16', 'Pindi Bhattian'], ['17', 'Jalalpur Bhattian'], ['18', 'Shakargarh'],
                ['19', 'Zafarwal'], ['20', 'Eminabad'], ['21', 'Nandipur'], ['22', 'Ahmad Nagar Chattha'],
                ['23', 'Kotli Loharan'], ['24', 'Bhopalwala'],
            ]],
            ['04', 'Rawalpindi Division', 'Punjab', [
                ['01', 'Rawalpindi'], ['02', 'Attock'], ['03', 'Jhelum'], ['04', 'Chakwal'], ['05', 'Murree'],
                ['06', 'Taxila'], ['07', 'Wah Cantonment'], ['08', 'Gujar Khan'], ['09', 'Kahuta'],
                ['10', 'Kallar Syedan'], ['11', 'Kotli Sattian'], ['12', 'Fateh Jang'], ['13', 'Pindi Gheb'],
                ['14', 'Jand'], ['15', 'Hassan Abdal'], ['16', 'Hazro'], ['17', 'Pind Dadan Khan'],
                ['18', 'Sohawa'], ['19', 'Dina'], ['20', 'Kallar Kahar'], ['21', 'Choa Saidan Shah'],
                ['22', 'Talagang'], ['23', 'Lawa'], ['24', 'Rawat'], ['25', 'Chakri'], ['26', 'Mandra'],
                ['27', 'Kamra'], ['28', 'Sanjeel'],
            ]],
            ['05', 'Sargodha Division', 'Punjab', [
                ['01', 'Sargodha'], ['02', 'Khushab'], ['03', 'Mianwali'], ['04', 'Bhakkar'],
                ['05', 'Bhalwal'], ['06', 'Shahpur'], ['07', 'Sahiwal Sargodha'], ['08', 'Kot Momin'],
                ['09', 'Sillanwali'], ['10', 'Jauharabad'], ['11', 'Nurpur Thal'], ['12', 'Quaidabad'],
                ['13', 'Mitha Tiwana'], ['14', 'Piplan'], ['15', 'Isa Khel'], ['16', 'Kundian'],
                ['17', 'Kalur Kot'], ['18', 'Darya Khan'], ['19', 'Mankera'], ['20', 'Jandanwala'],
                ['21', 'Farooka'], ['22', 'Sakesar'],
            ]],
            ['06', 'Faisalabad Division', 'Punjab', [
                ['01', 'Faisalabad'], ['02', 'Jhang'], ['03', 'Toba Tek Singh'], ['04', 'Chiniot'],
                ['05', 'Jaranwala'], ['06', 'Samundri'], ['07', 'Tandlianwala'], ['08', 'Gojra'],
                ['09', 'Kamalia'], ['10', 'Pir Mahal'], ['11', 'Shorkot'], ['12', 'Ahmedpur Sial'],
                ['13', 'Chenab Nagar'], ['14', 'Lalian'], ['15', 'Bhowana'], ['16', 'Chak Jhumra'],
                ['17', 'Khurrianwala'], ['18', 'Dijkot'], ['19', '18 Hazari'], ['20', 'Athara Hazari'],
                ['21', 'Painsra'], ['22', 'Mamunkanjan'],
            ]],
            ['07', 'Multan Division', 'Punjab', [
                ['01', 'Multan'], ['02', 'Khanewal'], ['03', 'Lodhran'], ['04', 'Vehari'],
                ['05', 'Jalalpur Pirwala'], ['06', 'Shujabad'], ['07', 'Mian Channu'], ['08', 'Kabirwala'],
                ['09', 'Jahanian'], ['10', 'Dunyapur'], ['11', 'Kehror Pacca'], ['12', 'Burewala'],
                ['13', 'Mailsi'], ['14', 'Gaggo'], ['15', 'Qadirpur Ran'], ['16', 'Bosan'],
            ]],
            ['08', 'Bahawalpur Division', 'Punjab', [
                ['01', 'Bahawalpur'], ['02', 'Bahawalnagar'], ['03', 'Rahim Yar Khan'],
                ['04', 'Ahmadpur East'], ['05', 'Yazman'], ['06', 'Hasilpur'], ['07', 'Khairpur Tamewali'],
                ['08', 'Chishtian'], ['09', 'Haroonabad'], ['10', 'Fort Abbas'], ['11', 'Minchinabad'],
                ['12', 'Sadiqabad'], ['13', 'Khanpur'], ['14', 'Liaquatpur'], ['15', 'Uch Sharif'],
                ['16', 'Dera Nawab Sahib'], ['17', 'Kahror Lal Esan'],
            ]],
            ['09', 'Dera Ghazi Khan Division', 'Punjab', [
                ['01', 'Dera Ghazi Khan'], ['02', 'Rajanpur'], ['03', 'Layyah'], ['04', 'Muzaffargarh'],
                ['05', 'Taunsa'], ['06', 'Kot Addu'], ['07', 'Jampur'], ['08', 'Rojhan'],
                ['09', 'Karor Lal Esan'], ['10', 'Chowk Azam'], ['11', 'Alipur'], ['12', 'Jatoi'],
                ['13', 'Kot Sultan'], ['14', 'Fatehpur'], ['15', 'Dajal'], ['16', 'Muhammadpur Diwan'],
                ['17', 'Chaubara'], ['18', 'Kot Mithan'],
            ]],
            ['10', 'Gujrat Division', 'Punjab', [
                ['01', 'Gujrat'], ['02', 'Wazirabad'], ['03', 'Mandi Bahauddin'],
                ['04', 'Kharian'], ['05', 'Lalamusa'], ['06', 'Jalalpur Jattan'], ['07', 'Sarai Alamgir'],
                ['08', 'Phalia'], ['09', 'Malakwal'], ['10', 'Dinga'], ['11', 'Kunjah'],
                ['12', 'Gulyana'], ['13', 'Pahrianwali'], ['14', 'Tanda'],
            ]],
            ['11', 'Mianwali Division', 'Punjab', [
                ['01', 'Mianwali'], ['02', 'Bhakkar'], ['03', 'Talagang'],
                ['04', 'Isa Khel'], ['05', 'Piplan'], ['06', 'Kundian'], ['07', 'Kalur Kot'],
                ['08', 'Darya Khan'], ['09', 'Mankera'], ['10', 'Lawa'], ['11', 'Chakrala'],
            ]],
            ['13', 'Peshawar Division', 'KPK', [
                ['01', 'Peshawar'], ['02', 'Charsadda'], ['03', 'Nowshera'], ['04', 'Khyber'], ['05', 'Mohmand'],
                ['06', 'Bajaur'], ['07', 'Kurram'], ['08', 'Jamrud'], ['09', 'Landi Kotal'], ['10', 'Bara'],
                ['11', 'Shabqadar'], ['12', 'Tangi'], ['13', 'Pabbi'], ['14', 'Risalpur'],
                ['15', 'Jehangira'], ['16', 'Ghalanai'], ['17', 'Khar Bajaur'], ['18', 'Parachinar'],
                ['19', 'Sadda'], ['20', 'Hayatabad'], ['21', 'Warsak'], ['22', 'Utmankhel'],
            ]],
            ['14', 'Mardan Division', 'KPK', [
                ['01', 'Mardan'], ['02', 'Swabi'],
                ['03', 'Takht Bhai'], ['04', 'Katlang'], ['05', 'Rustam'], ['06', 'Topi'],
                ['07', 'Lahor'], ['08', 'Tordher'], ['09', 'Yar Hussain'], ['10', 'Shewa'],
                ['11', 'Ghari Kapura'], ['12', 'Ismaila'],
            ]],
            ['15', 'Malakand Division', 'KPK', [
                ['01', 'Swat'], ['02', 'Mingora'], ['03', 'Dir Lower'], ['04', 'Dir Upper'], ['05', 'Chitral'], ['06', 'Shangla'], ['07', 'Buner'], ['08', 'Malakand'],
                ['09', 'Chitral Lower'], ['10', 'Chitral Upper'], ['11', 'Timergara'], ['12', 'Wari'],
                ['13', 'Drosh'], ['14', 'Booni'], ['15', 'Alpuri'], ['16', 'Puran'],
                ['17', 'Daggar'], ['18', 'Batkhela'], ['19', 'Chakdara'], ['20', 'Bahrain'],
                ['21', 'Kalam'], ['22', 'Barikot'], ['23', 'Matta'], ['24', 'Khwazakhela'],
                ['25', 'Saidu Sharif'],
            ]],
            ['16', 'Hazara Division', 'KPK', [
                ['01', 'Abbottabad'], ['02', 'Mansehra'], ['03', 'Haripur'], ['04', 'Battagram'], ['05', 'Kohistan'], ['06', 'Torghar'],
                ['07', 'Havelian'], ['08', 'Khanpur'], ['09', 'Ghazi'], ['10', 'Oghi'],
                ['11', 'Balakot'], ['12', 'Allai'], ['13', 'Upper Kohistan'], ['14', 'Lower Kohistan'],
                ['15', 'Kolai Palas'], ['16', 'Dassu'], ['17', 'Pattan'], ['18', 'Palas'],
                ['19', 'Lora'], ['20', 'Nawanshehr'], ['21', 'Sherwan'], ['22', 'Baffa'],
            ]],
            ['17', 'Kohat Division', 'KPK', [
                ['01', 'Kohat'], ['02', 'Hangu'], ['03', 'Karak'], ['04', 'Orakzai'],
                ['05', 'Lachi'], ['06', 'Thall'], ['07', 'Banda Daud Shah'], ['08', 'Gumbat'],
                ['09', 'Kalaya'], ['10', 'Doaba'], ['11', 'Shakardara'],
            ]],
            ['18', 'Bannu Division', 'KPK', [
                ['01', 'Bannu'], ['02', 'Lakki Marwat'], ['03', 'North Waziristan'],
                ['04', 'Domel'], ['05', 'Serai Naurang'], ['06', 'Miranshah'], ['07', 'Razmak'],
                ['08', 'Mir Ali'], ['09', 'Ghoriwala'], ['10', 'Baka Khel'], ['11', 'Naurang'],
            ]],
            ['19', 'Dera Ismail Khan Division', 'KPK', [
                ['01', 'Dera Ismail Khan'], ['02', 'Tank'], ['03', 'South Waziristan'],
                ['04', 'Paharpur'], ['05', 'Kulachi'], ['06', 'Paroa'], ['07', 'Wana'],
                ['08', 'South Waziristan Upper'], ['09', 'South Waziristan Lower'], ['10', 'Daraban'],
                ['11', 'Paniyala'], ['12', 'Prova'],
            ]],
            ['20', 'Karachi Division', 'Sindh', [
                ['01', 'Karachi'], ['02', 'Malir'], ['03', 'Korangi'], ['04', 'Keamari'], ['05', 'West'], ['06', 'Central'], ['07', 'East'], ['08', 'South'],
                ['09', 'Landhi'], ['10', 'Korangi Town'], ['11', 'Shah Faisal'], ['12', 'Gulshan-e-Iqbal'],
                ['13', 'Gulistan-e-Jauhar'], ['14', 'North Nazimabad'], ['15', 'North Karachi'], ['16', 'New Karachi'],
                ['17', 'Federal B Area'], ['18', 'Saddar'], ['19', 'Clifton'], ['20', 'DHA Karachi'],
                ['21', 'Lyari'], ['22', 'Orangi Town'], ['23', 'SITE'], ['24', 'Bin Qasim'],
                ['25', 'Gadap'], ['26', 'Liaquatabad'], ['27', 'Gulberg Karachi'], ['28', 'Baldia Town'],
            ]],
            ['21', 'Hyderabad Division', 'Sindh', [
                ['01', 'Hyderabad'], ['02', 'Jamshoro'], ['03', 'Tando Allahyar'], ['04', 'Tando Muhammad Khan'], ['05', 'Matiari'], ['06', 'Thatta'], ['07', 'Sujawal'], ['08', 'Badin'], ['09', 'Dadu'],
                ['10', 'Kotri'], ['11', 'Hala'], ['12', 'Sehwan'], ['13', 'Matli'], ['14', 'Talhar'],
                ['15', 'Tando Ghulam Hyder'], ['16', 'Makli'], ['17', 'Gharo'], ['18', 'Jati'],
                ['19', 'Bhit Shah'], ['20', 'Bhan Saeedabad'], ['21', 'Thano Bula Khan'], ['22', 'Golarchi'],
                ['23', 'Tando Bago'], ['24', 'Khairpur Nathan Shah'], ['25', 'Mehar'], ['26', 'Johi'],
            ]],
            ['22', 'Sukkur Division', 'Sindh', [
                ['01', 'Sukkur'], ['02', 'Khairpur'], ['03', 'Ghotki'],
                ['04', 'Rohri'], ['05', 'Pano Aqil'], ['06', 'Salehpat'], ['07', 'Mirpur Mathelo'],
                ['08', 'Daharki'], ['09', 'Ubauro'], ['10', 'Khanpur Mahar'], ['11', 'Kingri'],
                ['12', 'Gambat'], ['13', 'Kot Diji'], ['14', 'Ranipur'], ['15', 'Sobho Dero'],
                ['16', 'Pir jo Goth'], ['17', 'Faiz Ganj'],
            ]],
            ['23', 'Larkana Division', 'Sindh', [
                ['01', 'Larkana'], ['02', 'Shikarpur'], ['03', 'Jacobabad'], ['04', 'Kashmore'], ['05', 'Qambar Shahdadkot'],
                ['06', 'Ratodero'], ['07', 'Dokri'], ['08', 'Bakrani'], ['09', 'Shahdadkot'],
                ['10', 'Qubo Saeed Khan'], ['11', 'Miro Khan'], ['12', 'Thul'], ['13', 'Garhi Khairo'],
                ['14', 'Kandhkot'], ['15', 'Tangwani'], ['16', 'Karampur'], ['17', 'Madeji'],
                ['18', 'Lakhi'], ['19', 'Garhi Yasin'],
            ]],
            ['24', 'Shaheed Benazirabad Division', 'Sindh', [
                ['01', 'Nawabshah'], ['02', 'Naushahro Feroze'], ['03', 'Sanghar'],
                ['04', 'Sakrand'], ['05', 'Daur'], ['06', 'Qazi Ahmed'], ['07', 'Mehrabpur'],
                ['08', 'Bhiria'], ['09', 'Kandiaro'], ['10', 'Moro'], ['11', 'Shahdadpur'],
                ['12', 'Tando Adam'], ['13', 'Khipro'], ['14', 'Sinjhoro'], ['15', 'Jhol'],
                ['16', 'Shahpur Chakar'], ['17', 'Jam Sahib'],
            ]],
            ['25', 'Mirpur Khas Division', 'Sindh', [
                ['01', 'Mirpur Khas'], ['02', 'Umerkot'], ['03', 'Tharparkar'],
                ['04', 'Digri'], ['05', 'Kot Ghulam Muhammad'], ['06', 'Jhuddo'], ['07', 'Sindhri'],
                ['08', 'Mithi'], ['09', 'Islamkot'], ['10', 'Nagarparkar'], ['11', 'Chachro'],
                ['12', 'Diplo'], ['13', 'Kunri'], ['14', 'Pithoro'], ['15', 'Samaro'],
                ['16', 'Chhor'], ['17', 'Dhoro Naro'],
            ]],
            ['26', 'Quetta Division', 'Balochistan', [
                ['01', 'Quetta'], ['02', 'Pishin'], ['03', 'Killa Abdullah'], ['04', 'Chaman'], ['05', 'Mastung'],
                ['06', 'Kuchlak'], ['07', 'Sariab'], ['08', 'Hanna'], ['09', 'Khanozai'],
                ['10', 'Huramzai'], ['11', 'Barshore'], ['12', 'Dasht'],
            ]],
            ['27', 'Kalat Division', 'Balochistan', [
                ['01', 'Kalat'], ['02', 'Khuzdar'], ['03', 'Awaran'], ['04', 'Lasbela'], ['05', 'Hub'], ['06', 'Surab'],
                ['07', 'Wadh'], ['08', 'Uthal'], ['09', 'Bela'], ['10', 'Gadani'],
                ['11', 'Hub Chowki'], ['12', 'Dureji'], ['13', 'Mashkai'], ['14', 'Jhal Jhao'],
                ['15', 'Zeedi'],
            ]],
            ['28', 'Makran Division', 'Balochistan', [
                ['01', 'Gwadar'], ['02', 'Turbat'], ['03', 'Panjgur'],
                ['04', 'Pasni'], ['05', 'Jiwani'], ['06', 'Ormara'], ['07', 'Tump'],
                ['08', 'Mand'], ['09', 'Hoshab'], ['10', 'Buleda'], ['11', 'Kech'],
            ]],
            ['29', 'Sibi Division', 'Balochistan', [
                ['01', 'Sibi'], ['02', 'Kohlu'], ['03', 'Dera Bugti'], ['04', 'Harnai'], ['05', 'Ziarat'],
                ['06', 'Lehri'], ['07', 'Bakhtiarabad'], ['08', 'Sangan'], ['09', 'Pui'],
            ]],
            ['30', 'Naseerabad Division', 'Balochistan', [
                ['01', 'Naseerabad'], ['02', 'Jafarabad'], ['03', 'Jhal Magsi'], ['04', 'Sohbatpur'], ['05', 'Usta Muhammad'],
                ['06', 'Dera Murad Jamali'], ['07', 'Dera Allah Yar'], ['08', 'Gandawa'], ['09', 'Tamboo'],
                ['10', 'Chattar'],
            ]],
            ['31', 'Zhob Division', 'Balochistan', [
                ['01', 'Zhob'], ['02', 'Sherani'], ['03', 'Killa Saifullah'], ['04', 'Loralai'], ['05', 'Musakhel'], ['06', 'Barkhan'], ['07', 'Dukki'],
                ['08', 'Muslim Bagh'], ['09', 'Qamar Din Karez'], ['10', 'Kan Mehtarzai'],
            ]],
            ['32', 'Rakhshan Division', 'Balochistan', [
                ['01', 'Kharan'], ['02', 'Washuk'], ['03', 'Chagai'], ['04', 'Nushki'],
                ['05', 'Dalbandin'], ['06', 'Taftan'], ['07', 'Besima'], ['08', 'Mashkel'],
            ]],
            ['33', 'Loralai Division', 'Balochistan', [
                ['01', 'Loralai'], ['02', 'Musakhel'], ['03', 'Barkhan'], ['04', 'Dukki'],
                ['05', 'Mekhtar'], ['06', 'Kingri Balochistan'],
            ]],
            ['34', 'Islamabad Capital Territory', 'Islamabad', [
                ['01', 'Islamabad'],
                ['02', 'Bhara Kahu'], ['03', 'Tarnol'], ['04', 'Nilore'], ['05', 'Golra Sharif'],
                ['06', 'Koral'], ['07', 'Lohi Bher'], ['08', 'Chak Shahzad'], ['09', 'Ghouri Town'],
                ['10', 'Bahria Town Islamabad'], ['11', 'Rawat Islamabad'], ['12', 'Simly'],
                ['13', 'Banigala'], ['14', 'DHA Islamabad'],
            ]],
            ['35', 'Gilgit Division', 'Gilgit-Baltistan', [
                ['01', 'Gilgit'], ['02', 'Hunza'], ['03', 'Nagar'], ['04', 'Ghizer'],
                ['05', 'Karimabad'], ['06', 'Aliabad'], ['07', 'Gahkuch'], ['08', 'Punial'],
                ['09', 'Gupis'], ['10', 'Yasin'], ['11', 'Ishkoman'], ['12', 'Danyor'],
                ['13', 'Jutial'], ['14', 'Sust'], ['15', 'Passu'], ['16', 'Gupis-Yasin'],
            ]],
            ['36', 'Baltistan Division', 'Gilgit-Baltistan', [
                ['01', 'Skardu'], ['02', 'Ghanche'], ['03', 'Shigar'], ['04', 'Kharmang'],
                ['05', 'Khaplu'], ['06', 'Roundu'], ['07', 'Mehdiabad'], ['08', 'Khaplu Bala'],
            ]],
            ['37', 'Diamer Division', 'Gilgit-Baltistan', [
                ['01', 'Diamer'], ['02', 'Astore'],
                ['03', 'Chilas'], ['04', 'Tangir'], ['05', 'Darel'], ['06', 'Bunji'], ['07', 'Gorikot'],
            ]],
            ['38', 'Muzaffarabad Division', 'AJK', [
                ['01', 'Muzaffarabad'], ['02', 'Hattian Bala'], ['03', 'Neelum'],
                ['04', 'Athmuqam'], ['05', 'Sharda'], ['06', 'Garhi Dupatta'], ['07', 'Chikar'],
                ['08', 'Kel'], ['09', 'Dowarian'], ['10', 'Leepa'],
            ]],
            ['39', 'Mirpur Division', 'AJK', [
                ['01', 'Mirpur'], ['02', 'Bhimber'], ['03', 'Kotli'],
                ['04', 'Dadyal'], ['05', 'New Mirpur City'], ['06', 'Chakswari'], ['07', 'Samahni'],
                ['08', 'Barnala'], ['09', 'Nakyal'], ['10', 'Sehnsa'], ['11', 'Charhoi'],
            ]],
            ['40', 'Poonch Division', 'AJK', [
                ['01', 'Rawalakot'], ['02', 'Bagh'], ['03', 'Haveli'], ['04', 'Sudhnati'],
                ['05', 'Hajira'], ['06', 'Abbaspur'], ['07', 'Forward Kahuta'], ['08', 'Pallandri'],
                ['09', 'Trarkhel'], ['10', 'Thorar'], ['11', 'Dhirkot'],
            ]],
        ];
    }

    /**
     * @return list<array{0:string,1:string}>
     */
    public static function categories(): array
    {
        return [
            ['001', 'Grocery Store'],
            ['002', 'Garments Store'],
            ['003', 'Mobile Shop'],
            ['004', 'Pharmacy'],
            ['005', 'Electronics'],
            ['006', 'Restaurant'],
            ['007', 'Hardware'],
            ['008', 'Beauty'],
            ['009', 'Fuel Station'],
            ['010', 'General Store'],
            ['011', 'Footwear'],
            ['012', 'Jewelry'],
            ['013', 'Furniture'],
            ['014', 'Auto Parts'],
            ['015', 'Bookstore'],
            ['016', 'Stationery'],
            ['017', 'Cosmetics'],
            ['018', 'Meat / Butcher'],
            ['019', 'Bakery'],
            ['020', 'Fruits & Vegetables'],
            ['021', 'Dairy'],
            ['022', 'Tailor'],
            ['023', 'Optical'],
            ['024', 'Clinic'],
            ['025', 'Electrical'],
            ['026', 'Sanitary'],
            ['027', 'Paint'],
            ['028', 'Sports'],
            ['029', 'Toys'],
            ['030', 'Pet Shop'],
            ['031', 'Florist'],
            ['032', 'Dry Fruit'],
            ['033', 'Tea Stall'],
            ['034', 'Sweets'],
            ['035', 'Kiryana'],
            ['036', 'Wholesale'],
            ['037', 'Cloth Wholesale'],
            ['038', 'Kids Wear'],
            ['039', 'Ladies Garments'],
            ['040', 'Gents Garments'],
            ['041', 'Home Appliances'],
            ['042', 'Computer / IT'],
            ['043', 'CCTV'],
            ['044', 'Motorcycle'],
            ['045', 'Bicycle'],
            ['046', 'Tyre'],
            ['047', 'Battery'],
            ['048', 'Solar'],
            ['049', 'Mattress'],
            ['050', 'Department Store'],
        ];
    }

    /**
     * Serials that must never auto-issue. Admin gifts them.
     *
     * @return list<array{0:string,1:string}>
     */
    public static function golden(): array
    {
        return [
            ['C', '00000001'], ['C', '00000007'], ['C', '00000008'], ['C', '00000009'],
            ['C', '11111111'], ['C', '12345678'], ['C', '10000000'],
            ['C', '77777777'], ['C', '88888888'], ['C', '99999999'],
            ['C', '11223344'], ['C', '12121212'],
            ['M', '0000001'], ['M', '0000007'], ['M', '0000008'], ['M', '0000009'],
            ['M', '1111111'], ['M', '1234567'], ['M', '1000000'],
            ['M', '7777777'], ['M', '8888888'], ['M', '9999999'],
            ['M', '1212121'],
        ];
    }
}
