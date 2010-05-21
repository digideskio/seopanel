<?php

/***************************************************************************
 *   Copyright (C) 2009-2011 by Geo Varghese(www.seopanel.in)  	   *
 *   sendtogeo@gmail.com   												   *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
 ***************************************************************************/

# class defines all website controller functions
class WebsiteController extends Controller{

	# func to show websites
	function listWebsites($info=''){		
		$this->set('sectionHead', 'Websites Manager');
		$userId = isLoggedIn();
		
		if(isAdmin()){
			$sql = "select w.*,u.username from websites w,users u where u.id=w.user_id";
			$sql .= empty($info['userid']) ? "" : " and w.user_id=".$info['userid']; 
			$sql .= " order by w.name";			
			$this->set('isAdmin', 1);
			
			$userCtrler = New UserController();
			$userList = $userCtrler->__getAllUsers();
			$this->set('userList', $userList);
			
		}else{
			$sql = "select * from websites where user_id=$userId order by name";	
		}		
		$this->set('userId', empty($info['userid']) ? 0 : $info['userid']);		
		
		# pagination setup		
		$this->db->query($sql, true);
		$this->paging->setDivClass('pagingdiv');
		$this->paging->loadPaging($this->db->noRows, SP_PAGINGNO);
		$pagingDiv = $this->paging->printPages('websites.php?userid='.$info['userid']);		
		$this->set('pagingDiv', $pagingDiv);
		$sql .= " limit ".$this->paging->start .",". $this->paging->per_page;
				
		$websiteList = $this->db->select($sql);	
		$this->set('pageNo', $info['pageno']);		
		$this->set('list', $websiteList);
		$this->render('website/list');
	}

	# func to get all Websites
	function __getAllWebsites($userId='', $isAdminCheck=false){
		$sql = "select * from websites where status=1";
		if(!$isAdminCheck || !isAdmin() ){
			if(!empty($userId)) $sql .= " and user_id=$userId";
		} 
		$sql .= " order by name";
		$websiteList = $this->db->select($sql);
		return $websiteList;
	}
	
	# func to get all Websites
	function __getCountAllWebsites($userId=''){
		$sql = "select count(*) count from websites where status=1";
		if(!empty($userId)) $sql .= " and user_id=$userId";
		$countInfo = $this->db->select($sql, true);
		$count = empty($countInfo['count']) ? 0 : $countInfo['count']; 
		return $count;
	}
	
	# func to get all Websites having active keywords
	function __getAllWebsitesWithActiveKeywords($userId='', $isAdminCheck=false){
		$sql = "select w.* from websites w,keywords k where w.id=k.website_id and w.status=1 and k.status=1";
		if(!$isAdminCheck || !isAdmin() ){
			if(!empty($userId)) $sql .= " and user_id=$userId";
		} 
		$sql .= " group by w.id order by w.name";
		$websiteList = $this->db->select($sql);
		return $websiteList;
	}

	# func to change status
	function __changeStatus($websiteId, $status){
		$sql = "update websites set status=$status where id=$websiteId";
		$this->db->query($sql);
		
		$sql = "update keywords set status=$status where website_id=$websiteId";
		$this->db->query($sql);
	}

	# func to change status
	function __deleteWebsite($websiteId){
		$sql = "delete from websites where id=$websiteId";
		$this->db->query($sql);
		
		# delete all keywords under this website
		$sql = "select id from keywords where website_id=$websiteId";
		$keywordList = $this->db->select($sql);
		$keywordCtrler = New KeywordController();
		foreach($keywordList as $keywordInfo){
			$keywordCtrler->__deleteKeyword($keywordInfo['id']);
		}
	}

	function newWebsite($info=''){
		$this->set('sectionHead', 'New Website');
		$userId = isLoggedIn();
		if(!empty($info['check']) && !$this->__getCountAllWebsites($userId)){
			$this->set('msg', 'Please create a website before start to using seo tools and seo plugins.<br>Please <a href="javascript:void(0);" onclick="scriptDoLoad(\'websites.php\', \'content\')">activate</a> your website if you already created one.');
		}
		
		# get all users
		if(isAdmin()){
			$userCtrler = New UserController();
			$userList = $userCtrler->__getAllUsers();
			$this->set('userList', $userList);
			$this->set('userSelected', empty($info['userid']) ? $userId : $info['userid']);  			
			$this->set('isAdmin', 1);
		}
		
		$this->render('website/new');
	}

	function __checkName($name, $userId){
		$sql = "select id from websites where name='$name' and user_id=$userId";
		$listInfo = $this->db->select($sql, true);
		return empty($listInfo['id']) ? false :  $listInfo['id'];
	}

	function createWebsite($listInfo){
		$userId = empty($listInfo['userid']) ? isLoggedIn() : $listInfo['userid'];
		$this->set('post', $listInfo);
		$errMsg['name'] = formatErrorMsg($this->validate->checkBlank($listInfo['name']));
		$errMsg['url'] = formatErrorMsg($this->validate->checkBlank($listInfo['url']));
		if(!$this->validate->flagErr){
			if (!$this->__checkName($listInfo['name'], $userId)) {
				$sql = "insert into websites(name,url,title,description,keywords,user_id,status)
							values('{$listInfo['name']}','{$listInfo['url']}','".addslashes($listInfo['title'])."','".addslashes($listInfo['description'])."','".addslashes($listInfo['keywords'])."',$userId,1)";
				$this->db->query($sql);
				$this->listWebsites();
				exit;
			}else{
				$errMsg['name'] = formatErrorMsg('Website already exist!');
			}
		}
		$this->set('errMsg', $errMsg);
		$this->newWebsite($listInfo);
	}

	function __getWebsiteInfo($websiteId){
		$sql = "select * from websites where id=$websiteId";
		$listInfo = $this->db->select($sql, true);
		return empty($listInfo['id']) ? false :  $listInfo;
	}

	function editWebsite($websiteId, $listInfo=''){		
		$this->set('sectionHead', 'Edit Website');
		if(!empty($websiteId)){
			if(empty($listInfo)){
				$listInfo = $this->__getWebsiteInfo($websiteId);
				$listInfo['oldName'] = $listInfo['name'];
			}
			$listInfo['title'] = stripslashes($listInfo['title']);
			$listInfo['description'] = stripslashes($listInfo['description']);
			$listInfo['keywords'] = stripslashes($listInfo['keywords']);
			$this->set('post', $listInfo);
			
			# get all users
			if(isAdmin()){
				$userCtrler = New UserController();
				$userList = $userCtrler->__getAllUsers();
				$this->set('userList', $userList);  			
				$this->set('isAdmin', 1);
			}
			
			$this->render('website/edit');
			exit;
		}
		$this->listWebsites();
	}

	function updateWebsite($listInfo){
		$userId = empty($listInfo['user_id']) ? isLoggedIn() : $listInfo['user_id'];
		$this->set('post', $listInfo);
		$errMsg['name'] = formatErrorMsg($this->validate->checkBlank($listInfo['name']));
		$errMsg['url'] = formatErrorMsg($this->validate->checkBlank($listInfo['url']));
		if(!$this->validate->flagErr){

			if($listInfo['name'] != $listInfo['oldName']){
				if ($this->__checkName($listInfo['name'], $userId)) {
					$errMsg['name'] = formatErrorMsg('Website already exist!');
					$this->validate->flagErr = true;
				}
			}

			if (!$this->validate->flagErr) {
				$sql = "update websites set
						name = '{$listInfo['name']}',
						url = '{$listInfo['url']}',
						user_id = $userId,
						title = '".addslashes($listInfo['title'])."',
						description = '".addslashes($listInfo['description'])."',
						keywords = '".addslashes($listInfo['keywords'])."'
						where id={$listInfo['id']}";
				$this->db->query($sql);
				$this->listWebsites();
				exit;
			}
		}
		$this->set('errMsg', $errMsg);
		$this->editWebsite($listInfo['id'], $listInfo);
	}
	
	# func to crawl meta data of a website
	function crawlMetaData($websiteUrl) {
		
		if(!preg_match('/\w+/', $websiteUrl)) return;
		if(!stristr($websiteUrl, 'http://')) $websiteUrl = "http://".$websiteUrl;
		$ret = $this->spider->getContent($websiteUrl);
		if(!empty($ret['page'])){
			
			# meta title
			preg_match('/<TITLE>(.*?)<\/TITLE>/si', $ret['page'], $matches);
			if(!empty($matches[1])){
				$this->addInputValue($matches[1], 'webtitle');
			}
			
			# meta description
			preg_match('/<META.*?name="description".*?content="(.*?)"/si', $ret['page'], $matches);		
			if(empty($matches[1])){
				preg_match("/<META.*?name='description'.*?content='(.*?)'/si", $ret['page'], $matches);			
			}
			if(empty($matches[1])){
				preg_match('/<META content="(.*?)" name="description"/si', $ret['page'], $matches);					
			}
			if(!empty($matches[1])){
				$this->addInputValue($matches[1], 'webdescription');
			}
			
			# meta keywords
			preg_match('/<META.*?name="keywords".*?content="(.*?)"/si', $ret['page'], $matches);		
			if(empty($matches[1])){
				preg_match("/<META.*?name='keywords'.*?content='(.*?)'/si", $ret['page'], $matches);			
			}
			if(empty($matches[1])){
				preg_match('/<META content="(.*?)" name="keywords"/si', $ret['page'], $matches);			
			}
			if(!empty($matches[1])){
				$this->addInputValue($matches[1], 'webkeywords');
			}			
		} 
	}
	
	function addInputValue($value, $col) {

		$value = removeNewLines($value);
		?>
		<script type="text/javascript">
			document.getElementById('<?php echo $col;?>').value = '<?php echo addslashes($value);?>';
		</script>
		<?php
	}
}
?>