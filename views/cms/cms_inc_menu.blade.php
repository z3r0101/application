<?php

$sectionXML = $CMS_FN_MENU();

#REBUILD USER ACCESS DATA
$arrCMS_Users_Access = array();
if (CMS_Users_Type == 0) {
    #$sectionXML =  simplexml_load_string(file_get_contents(APPPATH.'views/cms/layout/cms_sections.xml'), "SimpleXMLElement", LIBXML_NOCDATA);
    if (isset($sectionXML["rebuild_user_access"])) {
        if ($sectionXML["rebuild_user_access"] == "true") {
            foreach ($sectionXML->children() as $tagObjects) {
                $sectionType = (!isset($tagObjects['type'])) ? $tagObjects['type'] : 'cms';
                $arrLink = explode('/', strval($tagObjects['link']));
                $arrRole = explode('|', strval($tagObjects['role']));
                $arrLinkMethodAccess = explode('|', strval($tagObjects['link_method_access']));
                $arrLinkAccess = strval($tagObjects['link_access']);
                $separator = strval($tagObjects['type']);

                if ($separator=='') {
                    if (isset($arrLink[1])) if ($arrLink[1]=='post') unset($arrLink[1]);
                    if (isset($arrLink[1])) if ($arrLink[1]=='list') unset($arrLink[1]);

                    $sectionId = $arrLink[0].(isset($arrLink[1]) ? ':'.$arrLink[1] : '');

                    if (in_array('editor', $arrRole, true)) {
                        $arrCMS_Users_Access[$sectionId]['options'] = array('view'=>false, 'url'=>$CONFIG['website']['path'].$CONFIG['cms']['route_name'].'/'.strval($tagObjects['link']), 'link_method_access'=>$arrLinkMethodAccess, 'access_options'=>array(), 'link_access'=>$arrLinkAccess);

                        $arrCMS_Users_Access[$sectionId]['items'] = array();
                        $arrSubItems = array();
                        foreach($tagObjects->children() as $tagSubObjects) {

                            if (strval($tagSubObjects->getName()) == 'sub') {
                                $arrLink = explode('/', strval($tagSubObjects['link']));
                                $sectionSubId = $arrLink[0].(isset($arrLink[1]) ? ':'.$arrLink[1] : '');
                                $arrSubItems[$sectionSubId]['options'] = array(
                                    'view'=>false,
                                    'url'=>$CONFIG['website']['path'].$CONFIG['cms']['route_name'].'/'.strval($tagSubObjects['link'])
                                );

                                $arrAccessOptions = array();
                                foreach($tagSubObjects->children() as $tagSubObjects) {
                                    if (strval($tagSubObjects->getName()) == 'cms_access') {
                                        foreach($tagSubObjects->children() as $cmsAccessOption) {
                                            $tId = $sectionSubId.'_'.((isset($cmsAccessOption['id'])) ? $cmsAccessOption['id'] : cmsTools::makeSlug(strval($cmsAccessOption)));
                                            $arrAccessOptions[$tId] = array('selected'=>false, 'caption'=>strval($cmsAccessOption));
                                        }
                                    }
                                }
                                $arrSubItems[$sectionSubId]['options']['access_options'] = $arrAccessOptions;
                            }

                        }
                        $arrCMS_Users_Access[$sectionId]['items'] = $arrSubItems;
                        #print_r($arrLink);

                        $arrAccessOptions = array();
                        foreach($tagObjects->children() as $tagSubObjects) {
                            if (strval($tagSubObjects->getName()) == 'cms_access') {
                                foreach($tagSubObjects->children() as $cmsAccessOption) {
                                    $tId = $sectionId.'_'.((isset($cmsAccessOption['id'])) ? $cmsAccessOption['id'] : cmsTools::makeSlug(strval($cmsAccessOption)));
                                    $arrAccessOptions[$tId] = array('selected'=>false, 'caption'=>strval($cmsAccessOption));
                                }
                            }
                        }
                        $arrCMS_Users_Access[$sectionId]['options']['access_options'] = $arrAccessOptions;
                    }
                }
            }

            $arrCMS_Users_Access_Temp = $arrCMS_Users_Access;
            #print '<pre>'; print_r($arrCMS_Users_Access_Temp); exit;

            $arrDataUsers = $self->dbClass->select("SELECT * FROM cms_users WHERE CMS_Users_Type = 1 AND CMS_Users_Website = '".$CONFIG['website']['domain']."'");
            for($i=0; $i<count($arrDataUsers); $i++) {
                $arrCMS_Users_Access = $arrCMS_Users_Access_Temp;
                $tCMS_Users_Access = '';
                if ($arrDataUsers[$i]["CMS_Users_Access"]!='') {
                    $tArrCMS_Users_Access = json_decode(base64_decode($arrDataUsers[$i]["CMS_Users_Access"]), true);

                    foreach($arrCMS_Users_Access as $Id => $Obj) {

                        if (isset($tArrCMS_Users_Access[$Id])) {

                            if (isset($tArrCMS_Users_Access[$Id]['options'])) {
                                $arrCMS_Users_Access[$Id]['options']['view'] = (isset($tArrCMS_Users_Access[$Id]['options']['view'])) ? $tArrCMS_Users_Access[$Id]['options']['view'] : false;

                                if (isset($tArrCMS_Users_Access[$Id]['options']['access_options'])) {
                                    foreach($tArrCMS_Users_Access[$Id]['options']['access_options'] as $cmsAccessKey => $cmsAccessObj) {
                                        $arrCMS_Users_Access[$Id]['options']['access_options'][$cmsAccessKey]['selected'] = (isset($tArrCMS_Users_Access[$Id]['options']['access_options'][$cmsAccessKey]['selected'])) ? $tArrCMS_Users_Access[$Id]['options']['access_options'][$cmsAccessKey]['selected'] : false;
                                    }
                                }
                            }

                            foreach($arrCMS_Users_Access[$Id]['items'] as $SubId => $SubObj) {
                                if (isset($tArrCMS_Users_Access[$Id]['items'][$SubId]['options'])) {
                                    $arrCMS_Users_Access[$Id]['items'][$SubId]['options']['view'] = (isset($tArrCMS_Users_Access[$Id]['items'][$SubId]['options']['view'])) ? $tArrCMS_Users_Access[$Id]['items'][$SubId]['options']['view'] : false;

                                    if (isset($tArrCMS_Users_Access[$Id]['items'][$SubId]['options']['access_options'])) {
                                        foreach($tArrCMS_Users_Access[$Id]['items'][$SubId]['options']['access_options'] as $cmsAccessKey => $cmsAccessObj) {
                                            $arrCMS_Users_Access[$Id]['items'][$SubId]['options']['access_options'][$cmsAccessKey]['selected'] = (isset($tArrCMS_Users_Access[$Id]['items'][$SubId]['options']['access_options'][$cmsAccessKey]['selected'])) ? $tArrCMS_Users_Access[$Id]['items'][$SubId]['options']['access_options'][$cmsAccessKey]['selected'] : false;
                                        }
                                    }
                                }
                            }

                        }

                    }

                }

                $tCMS_Users_Access = base64_encode(json_encode($arrCMS_Users_Access));

                $self->dbClass->update("cms_users",
                    array(
                        'CMS_Users_Access'=>$tCMS_Users_Access
                    ),
                    $arrDataUsers[$i]["CMS_Users_Id"]
                );
            }
        }
    }
}

#$sectionXML =  simplexml_load_string(file_get_contents(APPPATH.'views/cms/layout/cms_sections.xml'), "SimpleXMLElement", LIBXML_NOCDATA);
?>
<div id="cms-sidebar" class="navbar-nav cms-sidebar">
    <div class="cms-sidebar-brand"><img src="{!!(isset($CONFIG['cms']['resources']['logo']['src'])) ? RES_URL.$CONFIG['cms']['resources']['logo']['src'] : ' data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAAAjCAYAAABfLc7mAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTQ1IDc5LjE2MzQ5OSwgMjAxOC8wOC8xMy0xNjo0MDoyMiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTkgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkJERTEwRTc5QjM3NTExRUFBRTExRkZDNDEyQkEwN0I5IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkJERTEwRTdBQjM3NTExRUFBRTExRkZDNDEyQkEwN0I5Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6QkRFMTBFNzdCMzc1MTFFQUFFMTFGRkM0MTJCQTA3QjkiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6QkRFMTBFNzhCMzc1MTFFQUFFMTFGRkM0MTJCQTA3QjkiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz5zzotgAAADHElEQVR42uxZ7XHbMAytcvkfbVBlgjgTRJ6gygR1JkgyQd0J1ExgZ4K4E0iewOoEUiawN1CAOzCH4iCa/mptGe8OJ50EkQQeAZJQ1LbtF0N/cWEuMIINRrDBCDYYwQYj2GAEG4xgI9jQG2Aly6pZB/VvDJIE6g4C9RJs18fnJ6+cYLimrY4FSCYaKloPNtUj3Sd6/KYZT+/GyrtC9Jl26YaOq+tb2Zen/QykFm1OJDE0Ad6EXi39TbpjkCXTW8pxSoIvO8Y3BXmlexzQDxAcxDCKolLoDgMnc4jed7qic2Loa8XexXsOrmfWJvY7EmNsdojaFP0FUoHcg6AdKfkxEf0UIBi5P0FKGlNO/r4GHzSOXPp+BvJC3z7iMyQS9MZrU3TXzKeUgMi3mMmhegnLFoiRdNo+I1iJjDZQd609LCIT8XzCn7Nx5h22cn9jtNZKXzV/LiM4aJPlZhHNtEMhY5GF+HbCSy9GYcn85jCnq1yTfwt/l4pezLIqR6m094nLI3IKpskGjYOZN6OU1jcEp33wQxSo+u57uTPBSrrS1mmvHqUszA6/2IzGdTgDndkZ7bhTMbFxwk93aXMfESw3T9UWeplIYUjqhNL02RBMqfaO7lNKv/+XYC1at9DD9Lxy0Yq7Z0rTSPzDubBL0TrtyHj/hODVAdKSS8+qUS5N09qMj65OKBol0g10tTX7q6Jz5xvERSAJuUihh9o9D5ncK7tpNHjEKz50nx5ZKkc/4bHviRc0WKYqBYGPvADCvpuL3fLmtnecg2s67xWsGrMQg3CVoEKT0HMjOzPGyjvscymqWUvRr6voDJRzZN0xvsGu5+COdnNWnVowvxVs3PJ8P2I2FPw7pZIXYru3ktVQRUXOxkrZzb4GRvQ6vT+4axZVK15tQrISPFOCVHB/S1F/Q0sG7rin4nvNDt9SU24QnT57GreHgMstkXlDZ9g5jbOR6y7oVcwm1HuRu2ey/Zoqbj7b/17X3cQNP3YZjvyopW+yKL0MzEUnjWd5TI3sV2G/YT/8jWCDEWwwgg1GsMEINhjBBiPYCDb0Bh8CDACCOOOGlv42AQAAAABJRU5ErkJggg=='!!}" class="img-fluid" {!!(isset($CONFIG['cms']['resources']['logo']['style'])) ? 'style="'.$CONFIG['cms']['resources']['logo']['style'].'"' : 'style="height: 35px; margin: 5px"'!!}></div>
    <ul>
        <li class="cms-mobile-view"><a href="<?=$CONFIG['website']['path'].$CONFIG['cms']['route_name']."/administrator/my-account/post"?>"><i class="fas fa-user"></i> My Account</a></li>
    <?php
        foreach ($sectionXML->children() as $tagObjects) {
            $separator = strval($tagObjects['type']);
            $selection = (isset($tagObjects['selection'])) ? $tagObjects['selection'] : 'class';
            $Menu = (isset($tagObjects['menu'])) ? filter_var($tagObjects['menu'], FILTER_VALIDATE_BOOLEAN) : true;

            if ($separator == '') {
                if ($Menu) {
                    $sectionActive = $self->selectedUrlClass;
                    if ($selection == 'method')
                        $sectionActive = $self->selectedUrlMethod;

                    $_menuArr = explode('/', $tagObjects['link']);

                    $selectionUrl = $_menuArr[0];
                    if ($selection == 'method')
                        $selectionUrl = $_menuArr[1];

                    $tBadgeCount = "";
                    if (isset($tagObjects['badge_count'])) {
                        $db = new cmsDatabaseClass();
                        $arrData = $db->select(str_replace("COUNT(*)", "count(*) AS badge_count", $tagObjects['badge_count']));
                        if ($arrData[0]["badge_count"]>0)
                            $tBadgeCount = "<span class=\"badge badge-secondary\">".$arrData[0]["badge_count"]."</span>";
                    }

                    $tArrRole = explode('|', $tagObjects['role']);

                    if (in_array($self->cmsRole[CMS_Users_Type], $tArrRole, TRUE)) {
                        $tLink = strval($tagObjects['link']);
                        $arrLink = explode('/', $tLink);
                        while($arrLink[count($arrLink)-1]=='post' || $arrLink[count($arrLink)-1]=='list' || is_numeric($arrLink[count($arrLink)-1])) {
                            unset($arrLink[count($arrLink)-1]);
                        }
                        $tClassName = implode(':', $arrLink);
                        //$tClassName = cmsTools::makeSlug($menuObj['@attributes']['title']);


                        $tView = false;
                        $tAccess = $self->CMS_Users_Access;
                        if (isset($tAccess[$tClassName])) {
                            $tView = $tAccess[$tClassName]['options']['view'];
                        }
                        if (CMS_Users_Type == 0) $tView = true;

                        if ($tView) {
                            print '<li'.(($selectionUrl == $sectionActive) ? ' class="active '.$tClassName.'"' : ' class="not-active '.$tClassName.'"').'><a href="'.$CONFIG['website']['path'].$CONFIG['cms']['route_name'].'/'.$tagObjects['link'].'" class="cms-menu-head" onclick="/*return cmsMenuClick(this)*/">'.$tagObjects['title'].' '.$tBadgeCount.'</a>';
                            if (isset($tagObjects->sub)) {
                                print '<ul>';
                                $tMenuCounter = 0;
                                foreach($tagObjects->sub as $sIndex => $sMenuObj):

                                    $tLink = strval($sMenuObj['link']);
                                    $arrLink = explode('/', $tLink);
                                    while($arrLink[count($arrLink)-1]=='post' || $arrLink[count($arrLink)-1]=='list' || is_numeric($arrLink[count($arrLink)-1])) {
                                        unset($arrLink[count($arrLink)-1]);
                                    }
                                    $tSubClassName = implode(':', $arrLink);

                                    $tSubView = false;
                                    if (isset($tAccess[$tClassName]['items'][$tSubClassName])) {
                                        $tSubView = $tAccess[$tClassName]['items'][$tSubClassName]['options']['view'];
                                    }
                                    if (CMS_Users_Type == 0)
                                        $tSubView = true;

                                    if ($tSubView) {
                                        $menuLink = $CONFIG['website']['path'].$CONFIG['cms']['route_name'].'/'.$sMenuObj['link'];

                                        if (is_null($self->menuSubIndex)) {
                                            $isActive = (($arrLink[1] == $self->selectedUrlMethod)) ? true : false; #$tMenuCounter == $self->menuSubIndex ||
                                        } else {
                                            $isActive = (($tMenuCounter == $self->menuSubIndex)) ? true : false; #$tMenuCounter == $self->menuSubIndex ||
                                        }

                                        $tBadgeCount = "";
                                        if (isset($sMenuObj['badge_count'])) {
                                            $db = new cmsDatabaseClass();
                                            $arrData = $db->select(str_replace("COUNT(*)", "count(*) AS badge_count", $sMenuObj['badge_count']));
                                            if ($arrData[0]["badge_count"]>0)
                                                $tBadgeCount = "<span class=\"badge badge-secondary\">".$arrData[0]["badge_count"]."</span>";
                                        }

                                        print '<li'.(($isActive) ? ' class="active '.$tSubClassName.'"' : ' class="'.$tSubClassName.'"').'><a href="'.$menuLink.'">'.$sMenuObj['title'].' '.$tBadgeCount.'</a>';

                                        $tMenuCounter++;
                                    }

                                endforeach;
                                print '</ul>';
                            }
                            print '</li>';
                        }
                    }
                }
            } else {
                print '<li class="not-active separator"><hr class="dotted"></li>';
            }
        }
    ?>
        <li class="cms-mobile-view"><a href="<?=$CONFIG['website']['path'].$CONFIG['cms']['route_name']."/logout"?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>