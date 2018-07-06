<?php

namespace App\Http\Controllers;

use duzun\hQuery;

use Illuminate\Http\Request;

class indexController extends Controller
{   //показывает форму
    public function show(){
        return view("parseform");

    }
    /*принимает запрос от формы */
    public function parse(Request $req){
        $result=false;
        $q=$req->input("query");
        $parsed_input=$this->parseUserInput($q);
        //скачиваем страницу
        $curl=curl_init($parsed_input["url"]);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,30);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8 ( .NET CLR 3.5.30729)');
        $result=curl_exec($curl);
        //скачали. проверяем что все норм скачалось и если да то парсим
        $html="";
        if($result!==false){
            $html=$this->parseHTML($parsed_input["selectorlist"],$result);
        }

        //return  view("parseform")->with("data",$html);
        return $html;
    }
    /*парсит текст полученный от юзера и возвращает урл страницы и массив селекторов
    в том порядке как они стояли в тексте */
    protected function parseUserInput($query){
        $url_regexp="/http[s]{0,1}:\/\/[w]*[.]{0,1}[\/\w._-]*/imx";
        $selector_regexp="/(class|id)[\s]*[=][\s]*[\"]([\w]+)[\"]/i";
        $url_isset=preg_match_all($url_regexp,$query,$arr);
        if(false!=$url_isset){
            $result["url"]=$arr[0][0];
        }else{
            $result["url"]="";
        }
        $result["selectorlist"]=array();
        $selectors_isset=preg_match_all($selector_regexp,$query,$arr);
        $attr="";
        if(false!=$selectors_isset){
            for($i=0;$i<count($arr[1]);$i++){
                switch($arr[1][$i]){
                    case "id":{
                        $attr="#";
                        break;
                    }
                    case "class":{
                        $attr=".";
                        break;
                    }
                }

                $result["selectorlist"][$i]=[$attr,$arr[2][$i]];

            }
        }

        return $result;
    }
    /*чистит кусочки хтмл полученные по указанному юзером пути от лишних тегов */
    protected function HTMLCleaner($html){
        //чистим все кроме table,caption,col,colgroup,tbody,td,tfoot,th,thead,tr,h1,h2,h3,h4,h5,h6,p
        //это выражение находит все маркеры тэгов. далее проходим по ним и если такого
        //тега нет в списке допустимых то используя позиции вырезаем текст из исходного и вклеиваем в результат
        $tags_regexp="/<\/{0,1}[\s]*([\w]*)[\w\=\"\s_-]*[\s]*[\/]{0,1}>/i";
        $allow_tags=["table","caption","col","colgroup","tbody","td","tfoot","th","thead","tr","h1","h2","h3","h4","h5","h6","p"];
        $result=array();
        preg_match_all($tags_regexp,$html,$result,PREG_OFFSET_CAPTURE|PREG_PATTERN_ORDER);
        $text="";
        $lastpos=0;
        for($i=0;$i<count($result[1]);$i++){
            if(!in_array($result[1][$i][0],$allow_tags)){
                //если тег не входит в число разрешенных 
                $l=strlen($result[0][$i][0]);//получаем длину маркера тега
                $p=$result[0][$i][1];//получаем позицию маркера тега
                $text.=substr($html,$lastpos,$p-$lastpos);
                $lastpos=$p+$l;
            }
        }
        //копируем хвост
        $text.=substr($html,$lastpos,strlen($html));
        return $text;
    }
    /*производит выборку внутреннего содержимого тегов самого нижнего уровня из пути
    указанного юзером, чистит его и склеивает в кучу которую потом вернет */
    protected function parseHTML($tagArray,$html,$level=0){
        $doc = hQuery::fromHTML($html);
        $tags=$doc->find($tagArray[$level][0].$tagArray[$level][1]);
        $result="";
        
            foreach($tags as $tag){
                if($level<count($tagArray)-1){
                    $result.=$this->parseHTML($tagArray,$tag->html(),$level+1);
                }
                else{
                    $result.=$this->HTMLCleaner($tag->html());
                }
            }  
        
        return $result;
    }
}
