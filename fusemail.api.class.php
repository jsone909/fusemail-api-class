<?php

class FuseMail {
	private $platformUser;
	private $platformPass;
	
	//	Construct our FuseMail object
	public function __construct($platformUser, $platformPass) {
		$this->platformUser = $platformUser;
		$this->platformPass = $platformPass;
	}

	//	Public Methods - Methods supported by the FuseMail API
	public function placeOrder($data) {
		return $this->execute('order',$data);
	}
	public function suspendUser($user) {
		return $this->execute('suspend',array(
			'user' => $user
		));
	}
	public function enableUser($user) {
		return $this->execute('enable',array(
			'user' => $user
		));
	}
	public function terminateUser($user,$purge = false) {
		//	setting 'purge' to true will purge all data (such as removing the username) from our system.
		//	This process might take a few hours to complete. Leave blank otherwise.
		return $this->execute('terminate',array(
			'user' => $user,
			'purge' => ($purge ? 'yes' : '')
		));
	}
	public function addForward($user, $from, $to) {
		return $this->execute('addforward',array(
			'user' => $user,
			'forward_what' => $from,
			'forward_to' => $to
		));
	}
	public function removeForward($user, $from, $to) {
		return $this->execute('removeforward',array(
			'user' => $user,
			'forward_what' => $from,
			'forward_to' => $to
		));
	}
	public function removeAlias($user, $alias) {
		return $this->execute('removealias',array(
			'user' => $user,
			'alias' => $alias
		));
	}
	public function getForward($user, $from) {
		return $this->execute('getforward',array(
			'user' => $user,
			'forward_what' => $from
		));
	}
	public function checkalias($alias) {
		return $this->execute('checkalias',array(
			'alias' => $alias
		));
	}
	public function checkDomain($domain) {
		return $this->execute('checkdomain',array(
			'domain' => $domain
		));
	}
	public function addDomain($user,$domain) {
		return $this->execute('adddomain',array(
			'user' => $user,
			'domain' => $domain
		));
	}
	public function removeDomain($domain) {
		return $this->execute('removedomain',array(
			'domain' => $domain,
			'confirm' => 'yes'
		));
	}
	public function getReport($user = 'all',$subaccounts = false, $showextended = false) {
		//	default: show 'basic' reports for 'all' accounts, with 'no' subaccounts.
		//	show information about subaccounts as well? default: no
		$subaccounts = $subaccounts === true || $subaccounts == 'yes' ? 'yes' : 'no';
		$report_type = $showextended === true || $showextended == 'extended' ? 'extended' : 'basic';
		return $this->execute('report',array(
			'user' => $user,
			'group_subaccount' => $subaccounts,
			'report_type' => $report_type
		), true);		
	}
	public function getReportMail($user = 'all',$subaccounts = false, $type = 'all') {
		//	default: show 'all' info for 'all' accounts, with 'no' subaccounts.
		//	show information about subaccounts as well? default: no
		$subaccounts = $subaccounts === true || $subaccounts == 'yes' ? 'yes' : 'no';
		//	If the provided $type information does not exist, return 'all' info.
		$infotypes = array('all','alias','forwarder','autorespond','mailinglist');
		if (!in_array($type,$infotypes)) $type = 'all';
		
		return $this->execute('report',array(
			'user' => $user,
			'group_subaccount' => $subaccounts,
			'type' => $type
		), true);		
	}
	public function modifyAccount($data) {
		// we only need to send the data, we want modified.
		return $this->execute('modify', $data);
	}
	public function changeUsername($user,$newuser) {
		return $this->execute('changeusername', array(
			'user' => $user,
			'newuser' => $newuser
		));
	}

	//	Private Functions - Methods used by our FuseMail class
	private function execute($request, $params, $is_report = false) {
		$params = array_merge(array(
				'PlatformUser' => $this->platformUser,
				'PlatformPassword' => $this->platformPass,
				'request' => $request
			),$params);
		$fusemailUrl	= 'http://www.fusemail.com/api/request.html';
		$requestcontent = http_build_query($params,'','&');
		$size = strlen($requestcontent);
		$header = array(
			"POST /api/request.html HTTP/1.1",
			"Host: www.fusemail.com",
			"User-Agent: Mozilla/4.0",
			"Content-Length: {$size}",
			"Content-Type: application/x-www-form-urlencoded"
		);
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $fusemailUrl);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, $requestcontent);
		curl_setopt($c, CURLOPT_HTTPHEADER, $header);
		curl_setopt($c, CURLOPT_TIMEOUT, 100);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($c);
		curl_close($c);
		
		$data = explode('||',$data);
		$response['status']		= $data[0];
		$data = explode("\n",trim($data[1]),2);
		$response['response']	= trim($data[0]);
		if ($is_report) {
			$response['report'] = explode("\n",$data[1]);
		}
		
		return $response;
	}
}
?>