<?php

class GPON_SNMP {
	private $GPON;
	private $options = array();
	private $error = array();
	private $path_OID='SLE-GPON-MIB::';

	public function __construct($options = array(), &$GPON) {
		$this->GPON = &$GPON;
		$this->set_options($options);
		@snmp_read_mib('sle-gpon-mib.mib');
		@snmp_read_mib('snmpv2-mib.mib');
		@snmp_read_mib('DISMAN-EVENT-MIB.mib');
		@snmp_read_mib('sle-device-mib.mib');
		@snmp_read_mib('sle-systemmaintenance-mib.mib');
	}

	function set_options($options)
	{
		if(is_array($options) && count($options)>0)
		{
			$this->options=$options;
			if(!isset($this->options['snmp_privacy_passphrase']) || strlen($this->options['snmp_privacy_passphrase'])==0)
			{
				if(isset($this->options['snmp_password']))
				{
					$this->options['snmp_privacy_passphrase']=$this->options['snmp_password'];
				}
			}
		}
		return $this->test_options();
	}
	function get_options($key)
	{
		$key=trim($key);
		if(strlen($key)>0 && is_array($this->options) && count($this->options)>0)
		{
			if(isset($this->options[$key]))
			{
				return $this->options[$key];
			}
		}
	}
	function clear_options()
	{
		if(is_array($this->options) && count($this->options)>0)
		{
			$this->options=array();
		}
	}
	function test_options()
	{
		$result='';
		if(is_array($this->options) && count($this->options)>0)
		{
			if(isset($this->options['snmp_version']))
			{
				switch($this->options['snmp_version'])
				{
					case 1:
						if(!isset($this->options['snmp_community']))
						{
							$result='Błędny parametr: community SNMP';
						}
					break;
					case 2:
						if(!isset($this->options['snmp_community']))
						{
							$result='Błędny parametr: community SNMP';
						}
					break;
					case 3:
						if(!isset($this->options['snmp_username']) || strlen($this->options['snmp_username'])==0)
						{
							$result='Błędny parametr: security name(username) SNMP';
						}
						if(!isset($this->options['snmp_sec_level']) || strlen($this->options['snmp_sec_level'])==0)
						{
							$result='Błędny parametr: security level SNMP';
						}
						if(!isset($this->options['snmp_auth_protocol']) || strlen($this->options['snmp_auth_protocol'])==0)
						{
							$result='Błędny parametr: authentication protocol SNMP';
						}
						if(!isset($this->options['snmp_password']) || strlen($this->options['snmp_password'])==0)
						{
							$result='Błędny parametr: authentication pass phrase SNMP';
						}
						if(!isset($this->options['snmp_privacy_protocol']) || strlen($this->options['snmp_privacy_protocol'])==0)
						{
							$result='Błędny parametr: privacy protocol SNMP';
						}
						if(!isset($this->options['snmp_privacy_passphrase']) || strlen($this->options['snmp_privacy_passphrase'])==0)
						{
							$result='Błędny parametr: privacy pass phrase SNMP';
						}
					break;
					default:
						$result='Błędny parametr: wersja SNMP';
					break;
				}
				if(!isset($this->options['snmp_host']) || strlen($this->options['snmp_host'])==0)
				{
					$result='Błędny parametr: host SNMP';
				}
			}
		}
		else 
		{
			$result='Brak parametrów SNMP';
		}

		return $result;
	}
	function get_correct_connect_snmp()
	{
		$result='';
		$GponOltName=$this->get('sysName.0','SNMPv2-MIB::');
		$GponOltName=trim($GponOltName);
		if(strlen($GponOltName)==0)
		{
			$result='<br /><font color="red"><b>Błąd połączenia poprzez SNMP! Sprawdź konfigurację SNMP dla OLT.</b></font><br />';
		}
		return $result;
	}
	function parse_result_error($result=array())
	{
		$result=array_unique($result);
		$error=array();
		if(is_array($result) && count($result)>0)
		{
			foreach($result as $k=>$v)
			{
				if($v===false)
				{
					$last_error_snmp=error_get_last();
					if(isset($last_error_snmp['message']))
					{
						$error[]='Błąd: '.$last_error_snmp['message'];
					}
					else 
					{
						$error[]='Błąd';
					}
				}
			}	
		}
		$error=array_unique($error);
		return implode('<br />',$error);
	}
	function strToHex($string,$lenght=20)
	{
	    $hex='';
	    for ($i=0; $i < strlen($string); $i++)
	    {
	        $hex .= dechex(ord($string[$i]));
	    }
	    $lenght=intval($lenght);
	    if($lenght>0)
	    {
	    	if($lenght>strlen($hex))
	    	{
	    		$hex.=str_repeat('0',$lenght-strlen($hex));
	    	}
	    }
	    return $hex;
	}
	function hexToStr($hex)
	{
		$hex=str_replace(' ','',$hex);
	    $string='';
	    for ($i=0; $i < strlen($hex)-1; $i+=2)
	    {
	        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
	    }
	    return trim($string);
	}
	function search_array_key($data_array,$key)
	{
		if(is_array($data_array) && count($data_array)>0)
		{
			foreach($data_array as $k=>$v)
			{
				if(preg_match('/'.$key.'/',$k))
				{
					return true;
				}
			}
		}
		return false;
	}
	function search_array_key_value($data_array,$key)
	{
		if(is_array($data_array) && count($data_array)>0)
		{
			foreach($data_array as $k=>$v)
			{
				if(preg_match('/'.$key.'/',$k))
				{
					return $this->clean_snmp_value($v);
				}
			}
		}
	}
	function clean_snmp_value($value)
	{
		$value=str_replace('INTEGER: ','',$value);
		$value=str_replace('STRING: ','',$value);
		$value=str_replace('Counter64: ','',$value);
		$value=str_replace('BITS: ','',$value);
		$value=str_replace('Hex-STRING: ','',$value);
		$value=str_replace('Hex-','',$value);
		$value=str_replace('"','',$value);
		$value=str_replace('IpAddress: ','',$value);
		$value=str_replace('IpAddress:','',$value);
		$value=str_replace('Wrong Type (should be Gauge32 or Unsigned32): ','',$value);
		$value=$this->convert_snmp_value($value);
		$value=str_replace(' ','&nbsp;',$value);
		return $value;
	}
	function convert_snmp_value($value)
	{
		if(preg_match('/0.1dBm/',$value))
		{
			$tmp=explode(' ',$value);
			if(is_array($tmp) && count($tmp)>0)
			{
				if(isset($tmp[0]) && isset($tmp[1]))
				{
					$tmp[0]=$tmp[0]*0.1;
					$value=$tmp[0].'dBm';
				}
			}
		}
		if(preg_match('/1m/',$value))
		{
			$tmp=explode(' ',$value);
			if(is_array($tmp) && count($tmp)>0)
			{
				if(isset($tmp[0]) && isset($tmp[1]))
				{
					$value=$tmp[0].'m';
				}
			}
		}
		if(preg_match('/1 sec/',$value))
		{
			$tmp=explode(' ',$value);
			if(is_array($tmp) && count($tmp)>0)
			{
				if(isset($tmp[0]) && isset($tmp[1]))
				{
					$dni = floor($tmp[0]/60/60/24);
					$godzin = floor(($tmp[0]/60/60)-($dni*24));
					$minut = floor(($tmp[0]/60)-($dni*24*60)-($godzin*60));
					$sekund = floor($tmp[0]-($dni*24*60*60)-($godzin*60*60)-($minut*60));
					$value = "$dni d, ".str_pad($godzin, 2, "0", STR_PAD_LEFT).":".str_pad($minut, 2, "0", STR_PAD_LEFT).":".str_pad($sekund, 2, "0", STR_PAD_LEFT);
					//$value=$tmp[0].'sec';
				}
			}
		}
		if(preg_match('/1 hour/',$value))
		{
			$tmp=explode(' ',$value);
			if(is_array($tmp) && count($tmp)>0)
			{
				if(isset($tmp[0]) && isset($tmp[1]))
				{
					$value=$tmp[0].'h';
				}
			}
		}

		return $value;
	}
	function color_snmp_value($value)
	{
		switch($value)
		{
			case 'invalid(0)':
				$color='#CC0000';
			break;
			case 'inactive(1)':
				$color='#CC0000';
			break;
			case 'active(2)':
				$color='#00CC00';
			break;
			case 'running(3)':
				$color='#00CC00';
			break;
			default:
				$color='#000000';
			break;

		}
		return $value='<font color="'.$color.'">'.$value.'</font>';
	}
	function get_path_OID($path_OID='')
	{
	    $path_OID=trim($path_OID);
	    if(strlen($path_OID)>0)
	    {
	    	return $path_OID;
	    }
	    else 
	    {
	    	return $this->path_OID;
	    }
	}
	function get_max_last($array_data,$text_cut)
	{
		if(is_array($array_data) && count($array_data)>0)
		{
			foreach($array_data as $k=>$v)
			{
				if(preg_match('/'.$text_cut.'/',$k))
				{
					$keys[]=str_replace($text_cut,'',$k);
				}
			}
			return max($keys);
		}
		else 
		{
			return 0;
		}
	}
	function get_min_free($array_data,$text_cut)
	{
		if(is_array($array_data) && count($array_data)>0)
		{
			foreach($array_data as $k=>$v)
			{
				if(preg_match('/'.$text_cut.'/',$k))
				{
					$keys[]=str_replace($text_cut,'',$k);
				}
			}
			$max=max($keys);
			for($i=1;$i<$max;$i++)
			{
				if(array_search($i,$keys)===false)
				{
					return $i;
				}
			}
		}
		else 
		{
			return 1;
		}
	}
	function walk($OID,$path_OID='')
	{
		$result=false;
		$path_OID=$this->get_path_OID($path_OID);
		$OID=$path_OID.$OID;
		if(strlen($this->test_options())==0)
		{
			switch($this->get_options('snmp_version'))
			{
				case 1:
					$result=@snmpwalk($this->get_options('snmp_host'),$this->get_options('snmp_community'),$OID); 
				break;
				case 2:
					$result=@snmp2_real_walk($this->get_options('snmp_host'),$this->get_options('snmp_community'),$OID); 
				break;
				case 3:
					$result=@snmp3_real_walk($this->get_options('snmp_host'),$this->get_options('snmp_username'),$this->get_options('snmp_sec_level'),$this->get_options('snmp_auth_protocol'),$this->get_options('snmp_password'),$this->get_options('snmp_privacy_protocol'),$this->get_options('snmp_privacy_passphrase'),$OID);
				break;
				default:
				break;
			}
		}
		return $result;
	}
	function set($OID,$type,$value,$path_OID='')
	{
		$result=false;
		$path_OID=$this->get_path_OID($path_OID);
		$OID=$path_OID.$OID;
		if(strlen($this->test_options())==0)
		{
			switch($this->get_options('snmp_version'))
			{
				case 1:
					$result=@snmpset($this->get_options('snmp_host'),$this->get_options('snmp_community'),$OID,$type,$value); 
				break;
				case 2:
					$result=@snmp2_set($this->get_options('snmp_host'),$this->get_options('snmp_community'),$OID,$type,$value); 
				break;
				case 3:
					$result=@snmp3_set($this->get_options('snmp_host'),$this->get_options('snmp_username'),$this->get_options('snmp_sec_level'),$this->get_options('snmp_auth_protocol'),$this->get_options('snmp_password'),$this->get_options('snmp_privacy_protocol'),$this->get_options('snmp_privacy_passphrase'),$OID,$type,$value);
				break;
				default:
				break;
			}
		}
		return $result;
	}
	function get($OID,$path_OID='')
	{
		$result=false;
		$path_OID=$this->get_path_OID($path_OID);
		$OID=$path_OID.$OID;
		if(strlen($this->test_options())==0)
		{
			switch($this->get_options('snmp_version'))
			{
				case 1:
					$result=@snmpget($this->get_options('snmp_host'),$this->get_options('snmp_community'),$OID); 
				break;
				case 2:
					$result=@snmp2_get($this->get_options('snmp_host'),$this->get_options('snmp_community'),$OID); 
				break;
				case 3:
					$result=@snmp3_get($this->get_options('snmp_host'),$this->get_options('snmp_username'),$this->get_options('snmp_sec_level'),$this->get_options('snmp_auth_protocol'),$this->get_options('snmp_password'),$this->get_options('snmp_privacy_protocol'),$this->get_options('snmp_privacy_passphrase'),$OID);
				break;
				default:
				break;
			}
		}
		return $this->clean_snmp_value($result);
	}
	//--------------
	function ONU_delete($OLT_id,$ONU_id)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{
				$result[]=$this->set('sleGponOnuControlRequest','i',2);
				$result[]=$this->set('sleGponOnuControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuControlId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuControlTimer','u',2);
				$this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Deleted Onu '.$ONU_id.', olt '.$OLT_id);
			}
		}
		return array_unique($result);
	}
	
	function ONU_add($OLT_id,$ONU_name,$ONU_password='',$ONU_description='')
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_name=trim($ONU_name);
		$ONU_password=trim($ONU_password);
		$ONU_description=trim($ONU_description);
		if($OLT_id>0 && strlen($ONU_name)>0 && strlen($ONU_password)<11)
		{
			$onus=$this->walk('sleGponOnuSerial');
			//$ONU_id=intval($this->get_max_last($onus,$this->get_path_OID().'sleGponOnuSerial.'.$OLT_id.'.'))+1;
			$ONU_id=intval($this->get_min_free($onus,$this->get_path_OID().'sleGponOnuSerial.'.$OLT_id.'.'));
			if(!$this->search_array_key($onus,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{
				if($ONU_id>0)
				{
					$result[]=$this->set('sleGponOnuControlRequest','i',1);
					$result[]=$this->set('sleGponOnuControlOltId','i',$OLT_id);
					$result[]=$this->set('sleGponOnuControlId','i',$ONU_id);
					$result[]=$this->set('sleGponOnuControlSerial','s',$ONU_name);
					if(strlen($ONU_password)==0)
					{
						$result[]=$this->set('sleGponOnuControlPasswdMode','i',1);//auto-learning
					}
					else 
					{
						$result[]=$this->set('sleGponOnuControlPasswdMode','i',2);
						$result[]=$this->set('sleGponOnuControlPasswd','x',$this->strToHex($ONU_password));
					}
					$result[]=$this->set('sleGponOnuControlTimer','u',2);
					if(strlen($ONU_description)>0)
					{
						$this->ONU_set_description($OLT_id,$ONU_id,$ONU_description);
					}
					$result=array_unique($result);
					if(strlen($this->parse_result_error($result))==0)
					{
						$result['ONU_id']=$ONU_id;
					}
				}
			}
			$this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Added Onu '.$ONU_id.', serial '.$ONU_name.', olt '.$OLT_id);
		}
		return $result;	
	}
	function ONU_set_password($OLT_id,$ONU_id,$ONU_name,$ONU_password='')
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$ONU_name=trim($ONU_name);
		$ONU_password=trim($ONU_password);
		if($OLT_id>0 && $ONU_id>0 && strlen($ONU_name)>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{
				$result[]=$this->set('sleGponOnuControlRequest','i',1);
				$result[]=$this->set('sleGponOnuControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuControlId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuControlSerial','s',$ONU_name);
				if(strlen($ONU_password)==0)
				{
					$result[]=$this->set('sleGponOnuControlPasswdMode','i',1);//auto-learning
				}
				else 
				{
					$result[]=$this->set('sleGponOnuControlPasswdMode','i',2);
					$result[]=$this->set('sleGponOnuControlPasswd','x',$this->strToHex($ONU_password));
				}
				$result[]=$this->set('sleGponOnuControlTimer','u',1);
			}
		}
		return array_unique($result);
	}
	function ONU_set_description($OLT_id,$ONU_id,$ONU_description='')
	{
		$ogonek     = array("�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�");
		$bez_ogonek = array("a", "s", "z", "z", "c", "n", "l", "o", "e", "A", "S", "Z", "Z", "C", "N", "L", "O", "E");
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$ONU_description=trim($ONU_description);
		$ONU_description=str_replace($ogonek, $bez_ogonek, $ONU_description);
		if($OLT_id>0 && $ONU_id>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{
				$result[]=$this->set('sleGponOnuControlRequest','i',10);
				$result[]=$this->set('sleGponOnuControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuControlId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuControlDescription','s',$ONU_description);
				$result[]=$this->set('sleGponOnuControlTimer','u',2);
			}
		}
		return array_unique($result);
	}
	function ONU_get_host($OLT_id,$ONU_id)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
			$this->set('sleGponOnuHostControlRequest','i',2);
			$this->set('sleGponOnuHostControlOltId','i',$OLT_id);
			$this->set('sleGponOnuHostControlOnuId','i',$ONU_id);
			$this->set('sleGponOnuHostControlTimer','u',0);
			$result['Host Id']=$this->walk('sleGponOnuHostId.'.$OLT_id.'.'.$ONU_id);
			$result['Ip Option']=$this->walk('sleGponOnuHostIpOption.'.$OLT_id.'.'.$ONU_id);
			$result['Mac Address']=$this->walk('sleGponOnuHostMacAddress.'.$OLT_id.'.'.$ONU_id);
			$result['Current IP']=$this->walk('sleGponOnuHostCurIpAddr.'.$OLT_id.'.'.$ONU_id);
			$result['Current Mask']=$this->walk('sleGponOnuHostCurMask.'.$OLT_id.'.'.$ONU_id);
			$result['Current Gateway']=$this->walk('sleGponOnuHostCurGW.'.$OLT_id.'.'.$ONU_id);
			$result['Current Primary DNS']=$this->walk('sleGponOnuHostCurPriDns.'.$OLT_id.'.'.$ONU_id);
			$result['Current Secondary DNS']=$this->walk('sleGponOnuHostCurSecDns.'.$OLT_id.'.'.$ONU_id);
			$result['Domain name']=$this->walk('sleGponOnuHostDomainName.'.$OLT_id.'.'.$ONU_id);
			$result['Host name']=$this->walk('sleGponOnuHostHostName.'.$OLT_id.'.'.$ONU_id);
		}
		return $result;
	}
	function ONU_get_stat($OLT_id,$ONU_id)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
				
			$result['PonUnreceivedBursts']=$this->get('sleGponOltStatsOnuPonUnreceivedBursts.'.$OLT_id.'.'.$ONU_id);
			$result['PonPositiveDrift']=$this->get('sleGponOltStatsOnuPonPositiveDrift.'.$OLT_id.'.'.$ONU_id);
			$result['NegativeDrift']=$this->get('sleGponOltStatsOnuNegativeDrift.'.$OLT_id.'.'.$ONU_id);
			$result['PonBip8Errors']=$this->get('sleGponOltStatsOnuPonBip8Errors.'.$OLT_id.'.'.$ONU_id);
			$result['PonFecCorrectedBytes']=$this->get('sleGponOltStatsOnuPonFecCorrectedBytes.'.$OLT_id.'.'.$ONU_id);
			$result['PonFecUncorrectedCodewords']=$this->get('sleGponOltStatsOnuPonFecUncorrectedCodewords.'.$OLT_id.'.'.$ONU_id);
			$result['PonFecCorrectedCodewords']=$this->get('sleGponOltStatsOnuPonFecCorrectedCodewords.'.$OLT_id.'.'.$ONU_id);
			$result['PonFecReceivedCodewords']=$this->get('sleGponOltStatsOnuPonFecReceivedCodewords.'.$OLT_id.'.'.$ONU_id);
		}
		return $result;
	}
	function ONU_get_IgmpGroup_table($OLT_id,$ONU_id)
	{
		$result='';
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
			$this->set('sleGponIgmpGroupControlRequest','i',1);
			$this->set('sleGponIgmpGroupControlOltId','i',$OLT_id);
			$this->set('sleGponIgmpGroupControlOnuId','i',$ONU_id);
			$this->set('sleGponIgmpGroupControlTimer','u',0);
			$index=$this->walk('sleGponIgmpGroupIndex.'.$OLT_id.'.'.$ONU_id);
			$result='<b>Zarejestrowane Grupy Multicastowe:</b><br /><table border="1">';
			$result.='<tr><td><b>Index:</b></td><td><b>Id:</b></td><td><b>Typ:</b></td><td><b>SrcIPAddr:</b></td><td><b>DstIPAddr:</b></td><td><b>RptIPAddr:</b></td><td><b>Kanał TV:</b></td><td><b>JoinTime:</b></td><td><b>VlanId:</b></td></tr>';
			if(is_array($index) && count($index)>0)
			{
				$num=0;
				foreach($index as $k=>$v)
				{
					$num=intval($this->clean_snmp_value($v));
					$DstIPAddr=$this->get('sleGponIgmpGroupDstIPAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num);
					$RptIPAddr=$this->get('sleGponIgmpGroupRptIPAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num);
					$tv = $this->GPON->GetGponOnuTvChannel($DstIPAddr);
					$channel = $tv['channel'];
					$result.='<tr>
					<td>'.$num.'</td>
					<td>'.$this->get('sleGponIgmpGroupUniId.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponIgmpGroupUniType.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponIgmpGroupSrcIPAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$DstIPAddr.'</td>
					<td>'.$RptIPAddr.'</td>
					<td>' . $channel . '</td>
					<td>'.$this->get('sleGponIgmpGroupJoinTime.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponIgmpGroupVlanId.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					</tr>';
				}
			}
			$result.='</table>';
		}
		return $result;
	}
	function ONU_get_VoipLine_table($OLT_id,$ONU_id)
	{
		$result='';
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{	
			$this->set('sleGponOnuVoipLineControlRequest','i',1);
			$this->set('sleGponOnuVoipLineControlOltId','i',$OLT_id);
			$this->set('sleGponOnuVoipLineControlOnuId','i',$ONU_id);
			$this->set('sleGponOnuVoipLineControlTimer','u',0);
			$index=$this->walk('sleGponOnuVoipLineId.'.$OLT_id.'.'.$ONU_id);
			$result='<b>VoIP Line:</b><br /><table border="1">';
			$result.='<tr><td><b>Pots:</b></td><td><b>Line Status:</b></td><td><b>Used Codec:</b></td><td><b>Session Type:</b></td><td><b>1st Protocol Period:</b></td><td><b>1st Dest Addr:</b></td><td><b>2nd Protocol Period:</b></td><td><b>2nd Dest Addr:</b></td></tr>';
			if(is_array($index) && count($index)>0)
			{
				$num=0;
				foreach($index as $k=>$v)
				{
					$num=intval($this->clean_snmp_value($v));
					$result.='<tr>
					<td>'.$num.'</td>
					<td>'.$this->get('sleGponOnuVoipLineStatus.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLineUsedCodec.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLineSessionType.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLine1stProtocolPeriod.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLine1stDestAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLine2ndProtocolPeriod.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					<td>'.$this->get('sleGponOnuVoipLine2ndDestAddr.'.$OLT_id.'.'.$ONU_id.'.'.$num).'</td>
					</tr>';
				}
			}
			$result.='</table>';
		}
		return $result;
	}
	function ONU_get_UserMacOlt_table($OLT_id,$ONU_id)
	{
		//$OLT_id=3;
		//$ONU_id=3;
		$result='';
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
			$this->set('sleGponOnuUserMacControlRequest','i',4);
			$this->set('sleGponOnuUserMacControlOltId','i',$OLT_id);
			$this->set('sleGponOnuUserMacControlOnuId','i',$ONU_id);
			$this->set('sleGponOnuUserMacControlTimer','u',0);
			$MacOltId=$this->clean_arrays_strange_key('sleGponOnuUserMacOltId.'.$OLT_id.'.'.$ONU_id);
			$MacAddress=$this->clean_arrays_strange_key('sleGponOnuUserMacAddress.'.$OLT_id.'.'.$ONU_id);
			$MacOnuId=$this->clean_arrays_strange_key('sleGponOnuUserMacOnuId.'.$OLT_id.'.'.$ONU_id);
			$MacPortId=$this->clean_arrays_strange_key('sleGponOnuUserMacPortId.'.$OLT_id.'.'.$ONU_id);
			$MacVlanId=$this->clean_arrays_strange_key('sleGponOnuUserMacVlanId.'.$OLT_id.'.'.$ONU_id);
			$MacStatus=$this->clean_arrays_strange_key('sleGponOnuUserMacStatus.'.$OLT_id.'.'.$ONU_id);
			$result='<b>Wykryte adresy MAC na ONU:</b><br /><table border="1">';
			$result.='<tr><td><b>no:</b></td><td><b>OLT:</b></td><td><b>ONU:</b></td><td><b>MAC Address:</b></td><td><b>Producent:</b></td><td><b>GEM ID:</b></td><td><b>VID:</b></td><td><b>Status:</b></td></tr>';
			if(is_array($MacOltId) && count($MacOltId)>0)
			{
				$num=1;
				foreach($MacOltId as $k=>$v)
				{
					$mac=$this->clean_snmp_value($MacAddress[$k]);
					$mac_replace=str_replace('&nbsp;',':',trim($mac));
					$result.='<tr>
					<td>'.$num.'</td>
					<td>'.$this->clean_snmp_value($v).'</td>
					<td>'.$this->clean_snmp_value($MacOnuId[$k]).'</td>
					<td>'.$mac.'</td>
					<td>'.get_producer($mac_replace).'</td>
					<td>'.$this->clean_snmp_value($MacPortId[$k]).'</td>
					<td>'.$this->clean_snmp_value($MacVlanId[$k]).'</td>
					<td>'.$this->clean_snmp_value($MacStatus[$k]).'</td>
					</tr>';
					$num++;
				}
			}
			$result.='</table>';
		}
		return $result;
	}
	function clean_arrays_strange_key($key)
	{
		$table1=array();
		$table=$this->walk($key);
		if(is_array($table) && count($table)>0)
		{
			foreach($table as $k=>$v)
			{
				$k1=str_replace('""','',str_replace($this->path_OID.$key.'.','',$k));
				$k1_explode=explode('.',$k1);
				$k=$k1_explode[0].'_'.$k1_explode[count($k1_explode)-1];
				$table1[$k]=$v;
			}
		}
		return $table1;
	}
	function ONU_get_param($OLT_id,$ONU_id)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
			$result['Status']=$this->color_snmp_value($this->get('sleGponOnuStatus.'.$OLT_id.'.'.$ONU_id));
			$result['Serial']=$this->get('sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id);
			$result['Description']=$this->get('sleGponOnuDescription.'.$OLT_id.'.'.$ONU_id);
			    //Czasami description zwraca jako ciag hex (AB CD F0 12...)
			    // tyle ze to przechodzi przez clean_snmp_value: ' ' -> &nbsp;
			    if (preg_match ('/^[A-F0-9]{2}&nbsp\;[A-F0-9]{2}&nbsp\;/', $result['Description']))
			    {
				//jakies \n sa w tym description !?
				$result['Description'] = trim(preg_replace('/\s*/', '', $result['Description']));
				$tmp = preg_replace('/&nbsp;/', '', $result['Description']);
				$newtmp = pack('H*', $tmp);
				$result['Description'] = $newtmp;
			    }
		    	$result['Model Name']=$this->get('sleGponOnuModelName.'.$OLT_id.'.'.$ONU_id);
			$result['Profile']=$this->get('sleGponOnuProfile.'.$OLT_id.'.'.$ONU_id);
			$result['Deactive Reason']=$this->get('sleGponOnuDeactiveReason.'.$OLT_id.'.'.$ONU_id);
			$result['Rx Power']=$this->get('sleGponOnuRxPower.'.$OLT_id.'.'.$ONU_id);
			$result['Distance']=$this->get('sleGponOnuDistance.'.$OLT_id.'.'.$ONU_id);
			$result['Link Up Time']=$this->get('sleGponOnuLinkUpTime.'.$OLT_id.'.'.$ONU_id);
			$result['Inactive Time']=$this->get('sleGponOnuInactiveTime.'.$OLT_id.'.'.$ONU_id);
			//add human format
			if($result['Inactive Time'] > 86400)
			{
			    $result['Inactive Time'] .= $m=sprintf(" (%dd %02dh)", floor($result['Inactive Time']/86400), ($result['Inactive Time']%86400)/3600);
			}
			elseif($result['Inactive Time'] > 3600)
			{
			    $result['Inactive Time'] .= $m=sprintf(" (%dh %02dm)", floor($result['Inactive Time']/3600), ($result['Inactive Time']%3600)/60);
			}
			else
			{
			    $result['Inactive Time'] .= $m=sprintf(" (%dm %02ds)", floor($result['Inactive Time']/60), $result['Inactive Time']%60);
			}

			$result['Password']=$this->get('sleGponOnuPasswd.'.$OLT_id.'.'.$ONU_id);
			$result['Password mode']=$this->get('sleGponOnuPasswdMode.'.$OLT_id.'.'.$ONU_id);
			$result['Mac']=$this->get('sleGponOnuHostMacAddress.'.$OLT_id.'.'.$ONU_id.'.1');
			$result['OS1 Standby Version']=$this->get('sleGponOnuNosStandbyVersion.'.$OLT_id.'.'.$ONU_id);
			$result['OS2 Active Version']=$this->get('sleGponOnuNosActiveVersion.'.$OLT_id.'.'.$ONU_id);
			$result['Upgrade Status']=$this->get('sleGponOnuNosUpgradeStatus.'.$OLT_id.'.'.$ONU_id);
			$result['OLTMac']=$this->walk('sleGponOnuHostMacAddress.'.$OLT_id.'.'.$ONU_id);
			$result['Radius Status']=$this->get('sleGponOnuRadiusAuthStatus.'.$OLT_id.'.'.$ONU_id);
			$result['Radius Profile']=$this->get('sleGponOnuRadiusAuthReceiveProfile.'.$OLT_id.'.'.$ONU_id);

			//sh onu onu ani optic-module-info
			$AniId = 1; //na razie tylko 1?
			$this->set('sleGponOnuAniControlRequest','i',1);
			$this->set('sleGponOnuAniControlOltId','i',$OLT_id);
			$this->set('sleGponOnuAniControlOnuId','i',$ONU_id);
			$this->set('sleGponOnuAniControlAniId','i',$AniId);
			$this->set('sleGponOnuAniControlTimer','u',0);
			$result['DMI Tx'] = $this->get('sleGponOnuAniOpticModuleTxPower.'.$OLT_id.'.'.$ONU_id.'.'.$AniId);

			// olt rx power
			$this->set('sleGponOnuControlRequest','i',20); //updateOltRxPower(20)
			$this->set('sleGponOnuControlOltId','i',$OLT_id);
			$this->set('sleGponOnuControlId','i',$ONU_id);
			$this->set('sleGponOnuControlTimer','u',0);
			$result['OltRxPower'] = $this->get('sleGponOnuOltRxPower.'.$OLT_id.'.'.$ONU_id);
	
			//get user & passwd
			$this->set('sleGponOnuControlRequest','i',25);
			$this->set('sleGponOnuControlOltId','i',$OLT_id);
			$this->set('sleGponOnuControlId','i',$ONU_id);
			$this->set('sleGponOnuControlTimer','u',0);
			$result['AccountUserName'] = $this->get('sleGponOnuAccountUserName.'.$OLT_id.'.'.$ONU_id);
			$result['AccountPassword'] = $this->get('sleGponOnuAccountPassword.'.$OLT_id.'.'.$ONU_id);

			if( $this->GPON->OnuModelWithRF($result['Model Name']))
			{
				$rfid = 1; //tylko jeden port rf?
			    	$result['RF Signal'] = $this->get('sleGponOnuVideoAniOpticalSignalLevel.'.$OLT_id.'.'.$ONU_id.'.'.$rfid);
			}

			$result['XML_id'] = $this->get('sleGponOnuMgmtIpPathId.'.$OLT_id.'.'.$ONU_id);
			$result['XML_passwd'] = $this->get('sleGponOnuMgmtIpPathPassword.'.$OLT_id.'.'.$ONU_id);
			$result['XML_host'] = $this->get('sleGponOnuMgmtIpPathUrl.'.$OLT_id.'.'.$ONU_id);
			$result['XML_file'] = $this->get('sleGponOnuMgmtIpPathFileName.'.$OLT_id.'.'.$ONU_id);
			$result['XML_state'] = $this->get('sleGponOnuMgmtIpPathState.'.$OLT_id.'.'.$ONU_id);

		/*
			$result['EncMode']=$this->get('sleGponOnuEncMode.'.$OLT_id.'.'.$ONU_id);
			$result['Hostname']=$this->get('sleGponOnuHostname.'.$OLT_id.'.'.$ONU_id);
			$result['HwAddress']=$this->get('sleGponOnuHwAddress.'.$OLT_id.'.'.$ONU_id);
			$result['Nos Active Version']=$this->get('sleGponOnuNosActiveVersion.'.$OLT_id.'.'.$ONU_id);
			$result['Nos Standby Version']=$this->get('sleGponOnuNosStandbyVersion.'.$OLT_id.'.'.$ONU_id);
			$result['Olt Rx Power']=$this->get('sleGponOnuOltRxPower.'.$OLT_id.'.'.$ONU_id);
			$result['RTD']=$this->get('sleGponOnuRTD.'.$OLT_id.'.'.$ONU_id);
			$result['Authentication Status']=$this->get('sleGponOnuAuthenticationStatus.'.$OLT_id.'.'.$ONU_id);
			$result['Fec Mode US']=$this->get('sleGponOnuFecModeUS.'.$OLT_id.'.'.$ONU_id);
			$result['User Mac Cnt']=$this->get('sleGponOnuUserMacCnt.'.$OLT_id.'.'.$ONU_id);
			$result['Loopback Test Result']=$this->get('sleGponOnuLoopbackTestResult.'.$OLT_id.'.'.$ONU_id);
			$result['Loopback Test Result Avg']=$this->get('sleGponOnuLoopbackTestResultAvg.'.$OLT_id.'.'.$ONU_id);
			$result['Deactive Reason']=$this->get('sleGponOnuDeactiveReason.'.$OLT_id.'.'.$ONU_id);
			$result['Block Status']=$this->get('sleGponOnuBlockStatus.'.$OLT_id.'.'.$ONU_id);
			$result['Block Reason']=$this->get('sleGponOnuBlockReason.'.$OLT_id.'.'.$ONU_id);
			$result['Last Active Fail Reason']=$this->get('sleGponOnuLastActiveFailReason.'.$OLT_id.'.'.$ONU_id);
			$result['Onu EqD']=$this->get('sleGponOnuEqD.'.$OLT_id.'.'.$ONU_id);
			$result['Max Tcont']=$this->get('sleGponOnuMaxTcont.'.$OLT_id.'.'.$ONU_id);
			$result['Max Us Queue Per Tcont']=$this->get('sleGponOnuMaxUsQueuePerTcont.'.$OLT_id.'.'.$ONU_id);
			$result['Sys Up Time']=$this->get('sleGponOnuSysUpTime.'.$OLT_id.'.'.$ONU_id);
			$result['Vender Product']=$this->get('sleGponOnuVenderProduct.'.$OLT_id.'.'.$ONU_id);
			$result['Voip Avail Signal Protocol']=$this->get('sleGponOnuVoipAvailSignalProtocol.'.$OLT_id.'.'.$ONU_id);
			*/
		}
		return $result;
	}
	function style_gpon_tx_output_power_weak($rxpower,$style=1)
	{
		$result='';
		if(ConfigHelper::getConfig('gpon-dasan.tx_output_power_weak'))
		{
			$rxpower=(float)str_replace(',','.',str_replace('dBm','',$rxpower));
			$gpon_tx_output_power_weak=(float)str_replace(',','.',str_replace('dBm','',ConfigHelper::getConfig('gpon-dasan.tx_output_power_weak')));
			if($rxpower<=$gpon_tx_output_power_weak)
			{
				if($style==1)
				{
					$result=' style="background-color:#FF0000;color:#FFFFFF;" ';
				}
				else 
				{
					$result='#FF0000';
				}
			}
		}
		return $result;
	}
	
	
	function ONU_get_param_table($OLT_id,$ONU_id,$ONU_name='')
	{
		$result='';
		if($this->ONU_is_real($OLT_id,$ONU_id,$ONU_name))
		{
			$snmp_result=$this->ONU_get_param($OLT_id,$ONU_id);
			
			$result='Dane z dnia: <b>'.date('Y-m-d H:i:s').'</b><br /><br />';
			if(is_array($snmp_result) && count($snmp_result)>0)
			{
				$DMITxPower=$this->get('sleEthernetPortDMITxPower.'.$OLT_id,'SLE-DEVICE-MIB::');	
				$tlumienie=(float)str_replace(',','.',$DMITxPower)-(float)str_replace(',','.',str_replace('dBm','',$snmp_result['Rx Power']));
				if(!ConfigHelper::getConfig('gpon-dasan.view_onu_passwords'))
				{
					$snmp_result['AccountPassword'] = "*********";
				}

				$result.='
				<table border="0" cellspacing="2">
				<tr>
				<td>
				<b>Informacje o ONU:</b><br />
					<table border="1">
					<tr><td><b>ONU ID:</b></td><td>'.$ONU_id.'</td></tr>
					<tr><td><b>Nazwa S/N:</b></td><td>'.$snmp_result['Serial'].'</td></tr>
					<tr><td><b>Description:</b></td><td>'.$snmp_result['Description'].'</td></tr>
					<tr><td><b>Model:</b></td><td>'.$snmp_result['Model Name'].'</td></tr>
					<tr><td><b>ONU Profil:</b></td><td>'.$snmp_result['Profile'].'</td></tr>
					<tr><td><b>Status:</b></td><td>'.$snmp_result['Status'].'</td></tr>
					<tr><td><b>Powód odłączenia:</b></td><td>'.$snmp_result['Deactive Reason'].'</td></tr>
					<tr><td><b>Poziom sygnału 1490nm<br />odbieranego na ONU:</b></td><td'.$this->style_gpon_tx_output_power_weak($snmp_result['Rx Power']).'>'.$snmp_result['Rx Power'].'</td></tr>
					<tr><td><b>Tłumienie trasy do abonenta:</b></td><td>'.$tlumienie.' dBm</td></tr>
					<tr><td><b>Dystans:</b></td><td>'.$snmp_result['Distance'].'</td></tr>
					<tr><td><b>Czas pracy:</b></td><td>'.$snmp_result['Link Up Time'].'</td></tr>
					<tr><td><b>Czas nieaktywności:</b></td><td>'.$snmp_result['Inactive Time'].'</td></tr>
					<tr><td><b>Adres MAC ONU:</b></td><td>'.$snmp_result['Mac'].'</td></tr>
					<tr><td><b>OS1 Standby Version:</b></td><td>'.$snmp_result['OS1 Standby Version'].'</td></tr>
					<tr><td><b>OS2 Active Version:</b></td><td>'.$snmp_result['OS2 Active Version'].'</td></tr>
					<tr><td><b>Upgrade Status:</b></td><td>'.$snmp_result['Upgrade Status'].'</td></tr>
					<tr><td><b>DMI TX Power (1310nm):</b></td><td>'.$snmp_result['DMI Tx'].' dBm</td></tr>
					<tr><td><b>OLT RX Power:</b></td><td>'.$snmp_result['OltRxPower'].'</td></tr>
					<tr><td><b>Onu login:</b></td><td>'.$snmp_result['AccountUserName'].'</td></tr>
					<tr><td><b>Onu hasło:</b></td><td>'.$snmp_result['AccountPassword'].'</td></tr>
					<tr><td><b>Radius Auth:</b></td><td>'.$snmp_result['Radius Status'].'</td></tr>
					<tr><td><b>Radius Profile:</b></td><td>'.$snmp_result['Radius Profile'].'</td></tr>';
					if($snmp_result['RF Signal'])
					{
					    $result .= '<tr><td><b>RF power level:</b></td><td>'.$snmp_result['RF Signal'].'</td></tr>';
					}

					$result .= '<tr><td onmouseover="popup(\'0:File transfer completed successfully<br>\
							1:File transfer aborted successfully<br>\
							2:File deleted<br>\
							3:URL undefined or unreachable<br>\
							4:Failure to authenticate<br>\
							5:File transfer in progress<br>\
							6:Remote failure<br>\
							7:Local failure<br>\
							255: Unknown\')" onmouseout="pophide()">
							<b>XML status:</b></td><td>'.$snmp_result['XML_state'].'</td></tr>
						<tr><td><b>XML host:</b></td><td>'.$snmp_result['XML_host'].'</td></tr>
						<tr><td><b>XML plik:</b></td><td>'.$snmp_result['XML_file'].'</td></tr>
						<tr><td><b>XML user:</b></td><td>'.$snmp_result['XML_id'].'</td></tr>
						<tr><td><b>XML hasło:</b></td><td>'.$snmp_result['XML_passwd'].'</td></tr>';
					$result .= '</table><br />';
				$onu_stat=$this->ONU_get_stat($OLT_id,$ONU_id);

/*  Elmat na razie nie chce statystyk
				if(is_array($onu_stat) && count($onu_stat)>0)
				{
					$result.='<b>Statystyki odbierane przez OLT od tego ONU:</b><br /><table border="1">';
					foreach($onu_stat as $k=>$v)
					{
						$result.='<tr><td><b>'.$k.':</b></td><td>'.$this->clean_snmp_value($v).'</td></tr>';
					}
					
					$result.='</td></tr></table>';
				}
*/
				$result.='</td>
				<td valign="top">';
				$onu_host=$this->ONU_get_host($OLT_id,$ONU_id);
				if(is_array($onu_host) && count($onu_host)>0 && isset($onu_host['Host Id']) && is_array($onu_host['Host Id']))
				{
					$result.='<b>Adres IP przypisany do ONU (np. VoIP):</b><table border="0"><tr>';
					foreach($onu_host['Host Id'] as $k2=>$v2)
					{
						$current_ip = $this->clean_snmp_value($onu_host['Current IP'][$this->path_OID.'sleGponOnuHostCurIpAddr.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]);

						$result.='<td valign="top"><table border="1">';
						$result.='<tr><td><b>Host Id:</b></td><td>'.$this->clean_snmp_value($v2).'</td></tr>';
						$result.='<tr><td><b>Ip Option:</b></td><td>'.$this->clean_snmp_value($onu_host['Ip Option'][$this->path_OID.'sleGponOnuHostIpOption.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]).'</td></tr>';
						$result.='<tr><td><b>Mac Address:</b></td><td>'.$this->clean_snmp_value($onu_host['Mac Address'][$this->path_OID.'sleGponOnuHostMacAddress.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]).'</td></tr>';
						if($this->GPON->IsNotOldOnuModel($snmp_result['Model Name']) && $current_ip != '0.0.0.0')
							$result.='<tr><td><b>Current IP:</b></td><td><a href="http://'.$current_ip.':8080" target="_blank">'.$current_ip.' </a></td></tr>';
						else
							$result.='<tr><td><b>Current IP:</b></td><td>'.$current_ip.'</td></tr>';
						$result.='<tr><td><b>Current Mask:</b></td><td>'.$this->clean_snmp_value($onu_host['Current Mask'][$this->path_OID.'sleGponOnuHostCurMask.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]).'</td></tr>';
						$result.='<tr><td><b>Current Gateway:</b></td><td>'.$this->clean_snmp_value($onu_host['Current Gateway'][$this->path_OID.'sleGponOnuHostCurGW.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]).'</td></tr>';
						$result.='<tr><td><b>Current Primary DNS:</b></td><td>'.$this->clean_snmp_value($onu_host['Current Primary DNS'][$this->path_OID.'sleGponOnuHostCurPriDns.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]).'</td></tr>';
						$result.='<tr><td><b>Current Secondary DNS:</b></td><td>'.$this->clean_snmp_value($onu_host['Current Secondary DNS'][$this->path_OID.'sleGponOnuHostCurSecDns.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]).'</td></tr>';
						$result.='<tr><td><b>Domain name:</b></td><td>'.$this->clean_snmp_value($onu_host['Domain name'][$this->path_OID.'sleGponOnuHostDomainName.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]).'</td></tr>';
						$result.='<tr><td><b>Host name:</b></td><td>'.$this->clean_snmp_value($onu_host['Host name'][$this->path_OID.'sleGponOnuHostHostName.'.$OLT_id.'.'.$ONU_id.'.'.$this->clean_snmp_value($v2)]).'</td></tr>';
						$result.='</td></tr></table></td>';
					}
					$result.='</tr></table><br />';
					
				}
				$result.='<b>Status portów na ONT:</b><br />
					<table border="1">
					<tr><td><b>Port Id:</b></td><td><b>Oper Status:</b></td><td><b>Admin Status:</b></td><td><b>AutoNego:</b></td><td><b>Medium:</b></td><td><b>Speed:</b></td><td><b>Duplex:</b></td><td><b>Voip:</b></td><td><b>MACs:</b></td></tr>';
				$snmp_ports_id=$this->walk('sleGponOnuPortId.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_operstatus=$this->walk('sleGponOnuPortOperStatus.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_adminstatus=$this->walk('sleGponOnuPortAdminStatus.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_autonego=$this->walk('sleGponOnuPortAutoNego.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_mediummode=$this->walk('sleGponOnuPortMediumMode.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_speed=$this->walk('sleGponOnuPortSpeed.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_duplex=$this->walk('sleGponOnuPortDuplex.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_phonenumber=$this->walk('sleGponProfileVoIPOmciPhoneNumber.'.$OLT_id.'.'.$ONU_id);
				if(is_array($snmp_ports_id) && count($snmp_ports_id)>0)
				{
					foreach($snmp_ports_id as $k1=>$v1)
					{
						$portid=str_replace($this->path_OID.'sleGponOnuPortId.'.$OLT_id.'.'.$ONU_id.'.','',$k1);
						$portoperstatus=$this->clean_snmp_value($snmp_ports_operstatus[$this->path_OID.'sleGponOnuPortOperStatus.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
						$portadminstatus=$this->clean_snmp_value($snmp_ports_adminstatus[$this->path_OID.'sleGponOnuPortAdminStatus.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
						$autonego='';
						$mediummode='';
						$speed='';
						$phonenumber='';
						$macs ='';
						if(preg_match('/^ethernet/',$portid))
						{
							$autonego=$this->clean_snmp_value($snmp_ports_autonego[$this->path_OID.'sleGponOnuPortAutoNego.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
							$speed=$this->clean_snmp_value($snmp_ports_speed[$this->path_OID.'sleGponOnuPortSpeed.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
							$duplex=$this->clean_snmp_value($snmp_ports_duplex[$this->path_OID.'sleGponOnuPortDuplex.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
							$mediummode=$this->clean_snmp_value($snmp_ports_mediummode[$this->path_OID.'sleGponOnuPortMediumMode.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
							//mac table on port
							$macs ='';
							if(preg_match('/up/', $portoperstatus))
							{
							    $portidid = intval(preg_replace('/ethernet\./','',$portid));
							    $this->set('sleGponOnuMacControlRequest', 'i',1);
							    $this->set('sleGponOnuMacControlOltIndex','i',$OLT_id);
							    $this->set('sleGponOnuMacControlOnuIndex','i',$ONU_id);
							    $this->set('sleGponOnuMacControlSlotIndex', 'i',1); //ethernet(1)
							    $this->set('sleGponOnuMacControlPortIndex', 'i',$portidid);
							    $this->set('sleGponOnuMacControlTimer','u',0);
							    $PortMac=$this->clean_arrays_strange_key('sleGponOnuMacAddress.'.$OLT_id.'.'.$ONU_id.'.'.$portid);
							    if(is_array($PortMac) && count($PortMac)>0)
							    {
								foreach($PortMac as $m)
								{
								    $macs .= $this->clean_snmp_value($m).'<br>';
								}
							    }
							}
						}
						if(preg_match('/pot/',$portid))
						{
							$phonenumber=$this->clean_snmp_value($snmp_ports_phonenumber[$this->path_OID.'sleGponProfileVoIPOmciPhoneNumber.'.$OLT_id.'.'.$ONU_id.'.'.str_replace('pots.','',$portid)]);
						}
						$result.='<tr><td>'.$portid.'</td><td>'.$portoperstatus.'</td><td>'.$portadminstatus.'</td><td>'.$autonego.'</td><td>'.$mediummode.'</td><td>'.$speed.'</td><td>'.$duplex.'</td><td>'.$phonenumber.'</td><td>'.$macs.'</td></tr>';
					}
				}
				$result.='	<table>';
				$result.='<br />';
				/*//stara wersja - zła - ale zostawiam jakby to też było potrzebne
				$result.='<table border="1">
					<tr><td><b>Lp.</b></td><td><b>MAC:</b></td></tr>';
					if(is_array($snmp_result['OLTMac']) && count($snmp_result['OLTMac'])>0)
					{
						$i=1;
						foreach($snmp_result['OLTMac'] as $k=>$v)
						{
							$result.='<tr><td align="right">'.$i.'.</td><td>'.$this->clean_snmp_value($v).'</td></tr>';
							$i++;
						}
					}
					
					$result.='</table>';
					*/
					$result.=$this->ONU_get_UserMacOlt_table($OLT_id,$ONU_id);
				$result.='</td>';
				
				
				$result.='</tr>';
				$IgmpGroup_table=$this->ONU_get_IgmpGroup_table($OLT_id,$ONU_id);
				$VoipLine_table=$this->ONU_get_VoipLine_table($OLT_id,$ONU_id);
				$result.='</table>';
				$result.='<br /><table><tr><td>'.$IgmpGroup_table.'</td></tr></table>';
				$result.='<br /><table><tr><td>'.$VoipLine_table.'</td></tr></table>';
			}
		}
		else 
		{
			$result='<font color="red"><b>Wystąpił błąd!!! Inne ONU jest podłączone pod ten OLT<br />(OLT-port: '.$OLT_id.', ONU-ID: '.$ONU_id.', ONU-Serial: '.$this->ONU_GetSerial($OLT_id,$ONU_id).')</b></font>';
		}
		return $result;
	}
	function ONU_get_param_table_edit($OLT_id,$ONU_id,$id,$phonesvoip=array(),$ONU_name='')
	{
		//$OLT_id=1;
		//$ONU_id=7;
		$result='';
		if($this->ONU_is_real($OLT_id,$ONU_id,$ONU_name))
		{
			$onchange=' onchange="this.style.borderColor=\'red\';"';
			$snmp_result=$this->ONU_get_param($OLT_id,$ONU_id);
			
			$result='Dane z dnia: <b>'.date('Y-m-d H:i:s').'</b><br /><br />';
			if(is_array($snmp_result) && count($snmp_result)>0)
			{
				$result.='
				<FORM ID="myform" name="myform" METHOD="POST" ACTION="?m=gpononuedit&id='.$id.'">
				<input type="hidden" name="snmpsend" id="snmpsend" value="1" />
				<input type="hidden" name="onureset" id="onureset" value="0" />
				<input type="hidden" name="clear_mac" id="clear_mac" value="0" />
				<input type="hidden" name="save" id="save" value="1" />
				<table cellspacing="3" border="0" width="99%">
				<tr>
				<td rowspan="2" valign="top" width="40%">
					<table cellspacing="2" border="0" width="100%">
					';
				$snmp_result['Status']=trim($snmp_result['Status']);
				if(preg_match('/active\(2\)/',$snmp_result['Status']) || preg_match('/running/',$snmp_result['Status']))
				{
					$result.='
					<tr><td><b>ONU Reset:</b></td><td><input type="button" value="Reset" id="onu_reset" OnClick="document.getElementById(\'onureset\').value=1;this.form.submit();" /></td></tr>
					';
				}
				$result.='
					<tr><td><b>ONU ID:</b></td><td>'.$ONU_id.'</td></tr>
					<tr><td><b>Nazwa S/N:</b></td><td>'.$snmp_result['Serial'].'</td></tr>
					<tr><td><b>Description:</b></td><td><INPUT TYPE="TEXT" NAME="onu_description" id="onu_description" VALUE="'.$snmp_result['Description'].'" MAXLENGTH="32" '.$onchange.'/></td></tr>
					<tr><td><b>Model:</b></td><td>'.$snmp_result['Model Name'].'</td></tr>';
				
				
					$result.='<tr><td><b>ONU Profil:</b></td><td>';
					$profiles=$this->GPON_get_profiles();
					$result.='<SELECT NAME="onu_profile"'.$onchange.'>';
					if(is_array($profiles) && count($profiles)>0)
					{
						foreach($profiles as $k=>$v)
						{
							$result.='<OPTION VALUE="'.$v.'" ';
							if($snmp_result['Profile']==$v)
							{
								$result.='selected="selected"';
							}
							$result.=' >'.$v.'</OPTION>';
						}
					}
					$result.='</SELECT>';
					$result.='</td></tr>
					<tr><td><b>Wyczyść MAC:</b></td><td><input type="button" value="Wyczyść" id="clear_mac_button" OnClick="document.getElementById(\'clear_mac\').value=1;this.form.submit();" /></td></tr>
					<tr><td><b>Status:</b></td><td>'.$snmp_result['Status'].'</td></tr>
					<tr><td><b><IMG SRC="img/';
					if($snmp_result['Status']=='invalid(0)' || $snmp_result['Status']=='inactive(1)')
					{
						$result.='no';
					}
					$result.='access.gif" ALT=""></b></td><td>';
					$result.='<SELECT SIZE="1" NAME="onu_status"'.$onchange.'>';
					$result.='<OPTION VALUE="1"';
					if($snmp_result['Status']=='active(2)' || $snmp_result['Status']=='running(3)')
					{
						$result.=' selected="selected"';
					}
					$result.='>Podłączony</OPTION>';
					$result.='<OPTION VALUE="2"';
					if($snmp_result['Status']=='invalid(0)' || $snmp_result['Status']=='inactive(1)')
					{
						$result.=' selected="selected"';
					}
					$result.='>Odłączony</OPTION></SELECT>';
					
					$result.='</td></tr>
					<tr><td><b>Powód odłączenia:</b></td><td>'.$snmp_result['Deactive Reason'].'</td></tr>
					<tr><td><b>Poziom sygnału 1490nm<br />odbieranego na ONU:</b></td><td'.$this->style_gpon_tx_output_power_weak($snmp_result['Rx Power']).'>'.$snmp_result['Rx Power'].'</td></tr>
					<tr><td><b>Dystans:</b></td><td>'.$snmp_result['Distance'].'</td></tr>
					<tr><td><b>Czas pracy:</b></td><td>'.$snmp_result['Link Up Time'].'</td></tr>
					<tr><td><b>Adres MAC ONU:</b></td><td>'.$snmp_result['Mac'].'</td></tr>
					<tr><td><b>OS1 Standby Version:</b></td><td>'.$snmp_result['OS1 Standby Version'].'</td></tr>
					<tr><td><b>OS2 Active Version:</b></td><td>'.$snmp_result['OS2 Active Version'].'</td></tr>
					<tr><td><b>Upgrade Status:</b></td><td>'.$snmp_result['Upgrade Status'].'</td></tr>
					<tr><td><b>Onu login:</b></td><td><INPUT TYPE="TEXT" NAME="onuaccount_username" id="onuaccount_username" VALUE="'.$snmp_result['AccountUserName'].'" MAXLENGTH="32" disabled="disabled"/></td></tr>
					<tr><td><b>Onu hasło:</b></td><td>';

					if(ConfigHelper::getConfig('gpon-dasan.view_onu_passwords'))
					{
						$result .= '<INPUT TYPE="TEXT" NAME="onuaccount_password" id="onuaccount_password" VALUE="'.$snmp_result['AccountPassword'].'" MAXLENGTH="32" '.$onchange.'/>';
					}
					else
					{
						$result .= '*********';
					}
					$result.='
					<tr><td><b>XML host:</b></td><td><INPUT TYPE="TEXT" NAME="onu_xml_host" id="onu_xml_host" VALUE="'.$snmp_result['XML_host'].'" '.$onchange.'/></td></tr>
					<tr><td><b>XML plik:</b></td><td><INPUT TYPE="TEXT" NAME="onu_xml_file" id="onu_xml_file" VALUE="'.$snmp_result['XML_file'].'" '.$onchange.'/></td></tr>
					<tr><td><b>XML id:</b></td><td><INPUT TYPE="TEXT" NAME="onu_xml_id" id="onu_xml_id" VALUE="'.$snmp_result['XML_id'].'" '.$onchange.'/></td></tr>
					<tr><td><b>XML hasło:</b></td><td><INPUT TYPE="TEXT" NAME="onu_xml_haslo" id="onu_xml_haslo" VALUE="'.$snmp_result['XML_passwd'].'" '.$onchange.'/></td></tr>
					</td></tr></table><br />
					
				</td>
				<td valign="top" style="vertical-align:top">
					<table cellspacing="2" border="1">
					<tr><td><b>Port Id:</b></td><td><b>Admin Status:</b></td><td><b>AutoNego:</b></td><td><b>Medium:</b></td><td><b>Speed:</b></td><td><b>Duplex:</b></td><td><b>Voip:</b></td></tr>';
				$snmp_ports_id=$this->walk('sleGponOnuPortId.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_status=$this->walk('sleGponOnuPortOperStatus.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_autonego=$this->walk('sleGponOnuPortAutoNego.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_mediummode=$this->walk('sleGponOnuPortMediumMode.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_portspeed=$this->walk('sleGponOnuPortConfSpeed.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_portduplex=$this->walk('sleGponOnuPortConfDuplex.'.$OLT_id.'.'.$ONU_id);
				$snmp_ports_phonenumber=$this->walk('sleGponProfileVoIPOmciPhoneNumber.'.$OLT_id.'.'.$ONU_id);
				if(is_array($snmp_ports_id) && count($snmp_ports_id)>0)
				{
					foreach($snmp_ports_id as $k1=>$v1)
					{
						$portid=str_replace($this->path_OID.'sleGponOnuPortId.'.$OLT_id.'.'.$ONU_id.'.','',$k1);
						$portstatus=$this->clean_snmp_value($snmp_ports_status[$this->path_OID.'sleGponOnuPortAdminStatus.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
						$result.='<tr><td>'.$portid.'</td>';
						
						$result.='<td>
						<select name="onuport_'.$portid.'"'.$onchange.'>
						<option ';
						if($portstatus=='down(2)')
						{
							$result.='selected="selected"';
						}
						$result.=' value="2">down</option>
						<option ';
						if($portstatus=='up(1)' || strlen(trim($portstatus))==0)
						{
							$result.='selected="selected"';
						}
						$result.=' value="1">up</option>
						</select>
						</td>';
						$result.='<td>';
						$autonego='';
						if(preg_match('/ethernet/',$portid) && !preg_match('/virtual/',$portid))
						{
							$autonego=$this->clean_snmp_value($snmp_ports_autonego[$this->path_OID.'sleGponOnuPortAutoNego.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
							$result.='
							<select name="onuportautonego_'.$portid.'"'.$onchange.'>
							<option ';
							if($autonego=='on(1)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="1">on</option>
							<option ';
							if($autonego=='off(2)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="2">off</option>
							</select>
							';
						}
						$result.='</td><td>';
						$mediummode='';
						if(preg_match('/ethernet/',$portid) && !preg_match('/virtual/',$portid))
						{
							$mediummode=$this->clean_snmp_value($snmp_ports_mediummode[$this->path_OID.'sleGponOnuPortMediumMode.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
							$result.='
							<select name="onuportmediummode_'.$portid.'"'.$onchange.'>
							<option ';
							if($mediummode=='mdi(0)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="0">mdi</option>
							<option ';
							if($mediummode=='mdiX(1)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="1">mdiX</option>
							<option ';
							if($mediummode=='auto(2)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="2">auto</option>
							<option ';
							if($mediummode=='unknown(255)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="255">unknown</option>
							</select>
							';
						}
						$result.='</td><td>';
						$portspeed='';
						if(preg_match('/ethernet/',$portid) && !preg_match('/virtual/',$portid))
						{
							$portspeed=$this->clean_snmp_value($snmp_ports_portspeed[$this->path_OID.'sleGponOnuPortConfSpeed.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
							$result.='
							<select name="onuportspeed_'.$portid.'"'.$onchange.'>
							<option ';
							if($portspeed=='auto(0)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="0">auto</option>
							<option ';
							if($portspeed=='speed10(1)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="1">10</option>
							<option ';
							if($portspeed=='speed100(2)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="2">100</option>
							<option ';
							if($portspeed=='speed1000(3)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="2">1000</option>
							</select>
							';
						}
						$result.='</td><td>';
						$portduplex='';
						if(preg_match('/ethernet/',$portid) && !preg_match('/virtual/',$portid))
						{
							$portduplex=$this->clean_snmp_value($snmp_ports_portduplex[$this->path_OID.'sleGponOnuPortConfDuplex.'.$OLT_id.'.'.$ONU_id.'.'.$portid]);
							$result.='
							<select name="onuportduplex_'.$portid.'"'.$onchange.'>
							<option ';
							if($portduplex=='full(1)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="1">full</option>
							<option ';
							if($portduplex=='half(2)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="2">half</option>
							<option ';
							if($portduplex=='auto(0)')
							{
								$result.='selected="selected"';
							}
							$result.=' value="0">auto</option>
							</select>
							';
						}
						$result.='</td><td>';
						if(preg_match('/pot/',$portid))
						{
							$portid_temp=str_replace('pots.','',$portid);
							$phonenumber=$this->clean_snmp_value($snmp_ports_phonenumber[$this->path_OID.'sleGponProfileVoIPOmciPhoneNumber.'.$OLT_id.'.'.$ONU_id.'.'.$portid_temp]);
							$result.='<select name="phonesvoip_'.$portid.'"'.$onchange.'><option value="0">brak numeru</option>';
							if(is_array($phonesvoip) && count($phonesvoip)>0)
							{
								foreach ($phonesvoip as $k2=>$v2)
								{
									if(is_array($v2) && count($v2)>0)
									{
										$result.='<option value="'.$v2['id'].'"';
										if($v2['phone']==$phonenumber)
										{
											$result.='selected="selected"';
										}
										$result.='>'.$v2['phone'].'</option>';
									}
								}
							}
							$result.='</select>';
						}
					}
				}
				$result.='</table>
				</td></tr>
				<tr>
				<td>
				    <table cellspacing="2" border="1">
				    <tr><td><b>Host Id:</b></td><td><b>HostIpOption:</b></td><td><b>Host MAC:</b></td><td><b>IP:</b></td><td><b>gw:</b></td></tr>';
				$this->set('sleGponIgmpGroupControlRequest','i',1);
				$this->set('sleGponIgmpGroupControlOltId','i',$OLT_id);
				$this->set('sleGponIgmpGroupControlOnuId','i',$ONU_id);
				$this->set('sleGponIgmpGroupControlTimer','u',0);
				$snmp_HostId=$this->walk('sleGponOnuHostId.'.$OLT_id.'.'.$ONU_id);
				$snmp_HostIpOption=$this->walk('sleGponOnuHostIpOption.'.$OLT_id.'.'.$ONU_id);
				$snmp_HostCurIpAddr=$this->walk('sleGponOnuHostCurIpAddr.'.$OLT_id.'.'.$ONU_id);
				$snmp_HostCurMask=$this->walk('sleGponOnuHostCurMask.'.$OLT_id.'.'.$ONU_id);
				$snmp_HostCurGW=$this->walk('sleGponOnuHostCurGW.'.$OLT_id.'.'.$ONU_id);
				$snmp_HostMac=$this->walk('sleGponOnuHostMacAddress.'.$OLT_id.'.'.$ONU_id);
				foreach($snmp_HostId as $k1=>$v1)
				{
					$hostid=str_replace($this->path_OID.'sleGponOnuHostId.'.$OLT_id.'.'.$ONU_id.'.','',$k1);
					$HostIpOption=$this->clean_snmp_value($snmp_HostIpOption[$this->path_OID.'sleGponOnuHostIpOption.'.$OLT_id.'.'.$ONU_id.'.'.$hostid]);
					$HostMac=$this->clean_snmp_value($snmp_HostMac[$this->path_OID.'sleGponOnuHostMacAddress.'.$OLT_id.'.'.$ONU_id.'.'.$hostid]);
					$HostCurIpAddr=$this->clean_snmp_value($snmp_HostCurIpAddr[$this->path_OID.'sleGponOnuHostCurIpAddr.'.$OLT_id.'.'.$ONU_id.'.'.$hostid]);
					$HostCurMask=$this->clean_snmp_value($snmp_HostCurMask[$this->path_OID.'sleGponOnuHostCurMask.'.$OLT_id.'.'.$ONU_id.'.'.$hostid]);
					$HostCurGw=$this->clean_snmp_value($snmp_HostCurGW[$this->path_OID.'sleGponOnuHostCurGW.'.$OLT_id.'.'.$ONU_id.'.'.$hostid]);
					$result.='<tr><td>'.$hostid.'</td>';
					$result.='<td>'.$HostIpOption.'</td><td>'.$HostMac.'</td>';

					$result.='<td>';
					if(!preg_match('/enableDhcp/',$HostIpOption))
					{
						$result.='<INPUT TYPE="TEXT" name="hostip_'.$hostid.'" size="28" VALUE="'.$HostCurIpAddr.'/'.$HostCurMask.'" '.$onchange.'/>';
						$result.='</td><td>';
						$result.='<INPUT TYPE="TEXT" name="hostgw_'.$hostid.'" size="15" VALUE="'.$HostCurGw.'" '.$onchange.'/>';
					}
					else
						$result.= $HostCurIpAddr.'</td><td>'.$HostCurGw;

					$result.='</td></tr>';
				}
				$result.='</td>';
				$result.='</tr>';

				$result .= '</table>
				</td>
				</tr>
				<tr><td align="right" colspan="2"><input type="button" value="Zapisz zmiany na ONU" id="save_changes" OnClick="document.getElementById(\'save\').value=1;this.form.submit();" /></td></tr>
				</table>
				</form>
				';
			}
		}
		else 
		{
			$result='<font color="red"><b>Wystąpił błąd!!! Inne ONU jest podłączone pod ten OLT<br />(OLT-port: '.$OLT_id.', ONU-ID: '.$ONU_id.', ONU-Serial: '.$this->ONU_GetSerial($OLT_id,$ONU_id).')</b></font>';
		}
		return $result;
	}
	function OLT_set_ModelServiceProfile($model, $profile)
	{
		$result[]=$this->set('sleGponServiceProfileControlRequest','i',1);
		$result[]=$this->set('sleGponServiceProfileControlModelName','s', $model);
		$result[]=$this->set('sleGponServiceProfileControlProfileName','s', $profile);
		$result[]=$this->set('sleGponServiceProfileControlTimer','u',0);

		return $result;
	}
	function OLT_set_defaultServiceProfile($profile)
	{
		$result[]=$this->set('sleGponBaseControlRequest','i',3);
		$result[]=$this->set('sleGponBaseControlDeaultServiceProfile','s', $profile);
		$result[]=$this->set('sleGponBaseControlTimer','u',0);

		return $result;
	}
	function OLT_set_radiususernametype($type)
	{
		$result[]=$this->set('sleGponBaseControlRequest','i',13); //setOnuAuthRadiusUserName(13)
		$result[]=$this->set('sleGponBaseControlOnuAuthRadiusUserName','i',intval($type));
		$result[]=$this->set('sleGponBaseControlTimer','u',0);

		return $result;
	}
	function OLT_add_radius($radiusip, $radiuskey, $radiusport)
	{
		if($radiusport == 0)
			$radiusport = 1812;

		$result[]=$this->set('sleGponOnuAuthControlRequest','i', 1); //setOnuAuthServer(1)
		$result[]=$this->set('sleGponOnuAuthControlAddress','a', $radiusip);
		$result[]=$this->set('sleGponOnuAuthControlKey','s', $radiuskey);
		$result[]=$this->set('sleGponOnuAuthControlPort','i', $radiusport);
		$result[]=$this->set('sleGponOnuAuthControlTimer','u', 0);

		return $result;
	}
	function OLT_del_radius($id)
	{
		$radiusip = $this->get('sleGponOnuAuthAddress.'.$id);

		$result[]=$this->set('sleGponOnuAuthControlRequest','i', 2);
		$result[]=$this->set('sleGponOnuAuthControlAddress','a', $radiusip);
		$result[]=$this->set('sleGponOnuAuthControlTimer','u', 0);

		return $result;
	}
	function OLT_set_AgingTime($OLT_id, $atime)
	{
		$result[]=$this->set('sleGponOltControlRequest','i', 48);
		$result[]=$this->set('sleGponOltControllndex','i', $OLT_id);
		$result[]=$this->set('sleGponOltControlOnuInactiveAgingTime','i', $atime);
		$result[]=$this->set('sleGponOltControlTimer','u', 0);

		return $result;
	}
	function OLT_set_FWAutoUpgrade($OLT_id, $status)
	{
		$result[]=$this->set('sleGponOnuFWAutoUpgradeControlRequest','i', 1);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeControlOltId','i', $OLT_id);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeControlMode','i', $status);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeControlTimer','u', 0);

		return $result;
	}
	function OLT_set_AuthMode($OLT_id, $mode)
	{
		$result[]=$this->set('sleGponOltControlRequest','i', 50); //setOnuAuthControlMode (50)
		$result[]=$this->set('sleGponOltControllndex','i', $OLT_id);
		$result[]=$this->set('sleGponOltControlOnuAuthControlMode', 'i', $mode);
		$result[]=$this->set('sleGponOltControlTimer','u', 0);

		return $result;
	}
	function OLT_set_autoupgrade_time($model,$start,$end,$reboot)
	{
		$model = trim($model);
		$start = intval($start);
		$end = intval($end);
		$reboot = intval($reboot);

		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlRequest', 'i', 1); //setOnuFWAutoUpgradeMTime
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlModelName', 's', $model);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlStartTime', 'i', $start);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlEndTime', 'i', $end);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlTimer', 'u', 0);

		if($reboot > -1)
		{
		    $result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlRequest', 'i', 3); //setRebootTime
		    $result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlModelName', 's', $model);
		    $result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlRebootTime', 'i', $reboot);
		    $result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlTimer', 'u', 0);
		}

		return $result;
	}
	function OLT_del_autoupgrade_time($model)
	{
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlRequest', 'i', 2);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlModelName', 's', $model);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlTimer', 'u', 0);

		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlRequest', 'i', 3);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlModelName', 's', $model);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlRebootTime', 'i', -1);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeMTimeControlTimer', 'u', 0);

		return $result;
	}
	function OLT_set_autoupgrade_model($model,$fwname,$ipaddr,$user,$pass,$version,$exclude)
	{
		$model = trim($model);
		$fwname = trim($fwname);
		$user = trim($user);
		$pass = trim($pass);
		$version = trim($version);

		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlRequest', 'i', 1); //setOnuFWAutoUpgradeModelProfile(1)
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlName', 's', $model);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlFWName', 's', $fwname);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlMethod', 'i', 1); // ftp(1)
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlServerAddress', 'a', $ipaddr);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlUser', 's', $user);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlPasswd', 's', $pass);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlTimer', 'u', 0);

		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlRequest', 'i', 3); //modifyOnuAutoUpgradeTargetVer(3)
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlName', 's', $model);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlFWTargetVersion', 's', $version);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlExclude', 'i', $exclude);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlTimer', 'u', 0);
		return $result;
	}
	function OLT_del_autoupgrade_model($model)
	{
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlRequest', 'i', 2);//destroyOnuAutoUpgradeFirmware(2)
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlName', 's', $model);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlTimer', 'u', 0);

		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlRequest', 'i', 4); //delGponOnuFWAutoUpgradeModelTargetVer(4)
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlName', 's', $model);
		$result[]=$this->set('sleGponOnuFWAutoUpgradeModelControlTimer', 'u', 0);

		return $result;
	}
	function OLT_write_config ()
	{
		// do tego trzeba dodac @snmp_read_mib('dasan-switch-mib.mib'); w 'function GPON_SNMP'
		#$result[]=$this->set('dsWriteConfig.0', 'i', 1, 'DASAN-SWITCH-MIB::'); //..ale podobno niekoszerne

		$result[]=$this->set('sleSystemControlRequest', 'i', 11, 'SLE-SYSTEMMAINTENANCE-MIB::'); //setSystemBaseBackup(11)
		$result[]=$this->set('sleSystemControlBackupFlag', 'i', 1, 'SLE-SYSTEMMAINTENANCE-MIB::'); // writeMemory(1)
		$result[]=$this->set('sleSystemControlTimer', 'u', 0, 'SLE-SYSTEMMAINTENANCE-MIB::');

	}
	function ONU_set_xml_path ($OLT_id, $ONU_id, $login, $passwd, $url, $file)
	{
	        $result[]=$this->set('sleGponOnuControlRequest', 'i', 28); //setOnuMgmtIpPathProtocol(28)
	        $result[]=$this->set('sleGponOnuControlOltId', 'i', $OLT_id);
	        $result[]=$this->set('sleGponOnuControlId', 'i', $ONU_id);
	        $result[]=$this->set('sleGponOnuControlMgmtIpPathProtocol', 'i' , 1); //ftp(1)
	        $result[]=$this->set('sleGponOnuControlMgmtIpPathId', 's' , $login);
	        $result[]=$this->set('sleGponOnuControlMgmtIpPathPassword', 's' , $passwd);
	        $result[]=$this->set('sleGponOnuControlTimer', 'u', 0);
	
	        $result[]=$this->set('sleGponOnuControlRequest', 'i', 29); //setOnuMgmtIpPathInfo(29)
	        $result[]=$this->set('sleGponOnuControlOltId', 'i', $OLT_id);
	        $result[]=$this->set('sleGponOnuControlId', 'i', $ONU_id);
	        $result[]=$this->set('sleGponOnuControlMgmtIpPathUrl', 's' , $url);
	        $result[]=$this->set('sleGponOnuControlMgmtIpPathFilename', 's' , $file);
	        $result[]=$this->set('sleGponOnuControlTimer', 'u', 0);
		$this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'XML added to '.$ONU_id.', olt '.$OLT_id.', url '.$url.' file '.$file);
	}
	function ONU_clear_xml_path ($OLT_id, $ONU_id)
	{
	        $result[]=$this->set('sleGponOnuControlRequest', 'i', 30); //clearOnuMgmtIpPath(30)
	        $result[]=$this->set('sleGponOnuControlOltId', 'i', $OLT_id);
	        $result[]=$this->set('sleGponOnuControlId', 'i', $ONU_id);
	        $result[]=$this->set('sleGponOnuControlTimer', 'u', 0);
		$this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'XML removed from '.$ONU_id.', olt '.$OLT_id);
	}

	function OLT_ONU_walk_signal()
	{
		return $this->walk('sleGponOnuRxPower');
	}

	function OLT_ONU_walk()
	{
		$result=array();
		$result['Distance']=$this->walk('sleGponOnuDistance');
		$result['RxPower']=$this->walk('sleGponOnuRxPower');
		$result['Profile']=$this->walk('sleGponOnuProfile');
		$result['Status']=$this->walk('sleGponOnuStatus');
		$result['DeactiveReason']=$this->walk('sleGponOnuDeactiveReason');
		
		return $result;
	}
	function OLT_ONU_walk_get_param()
	{
		$result1=array();
		$result=$this->OLT_ONU_walk();
		if(is_array($result) && count($result)>0)
		{
			foreach($result as $k=>$v)
			{
				if(is_array($v) && count($v)>0)
				{
					foreach($v as $k1=>$v1)
					{
						$k1=str_replace($this->path_OID.'sleGponOnuDistance.','',$k1);
						$k1=str_replace($this->path_OID.'sleGponOnuRxPower.','',$k1);
						$k1=str_replace($this->path_OID.'sleGponOnuProfile.','',$k1);
						$k1=str_replace($this->path_OID.'sleGponOnuStatus.','',$k1);
						$k1=str_replace($this->path_OID.'sleGponOnuDeactiveReason.','',$k1);
						
						$v1=$this->clean_snmp_value($v1);
						if($k=='Status')
						{
							$v1=$this->color_snmp_value($v1);
						}
						$result1[$k][$k1]=$v1;
					}
				}
			}
		}
		return $result1;
	}
	function OLT_get_param_edit($OLT_id=0)
	{
		$onchange=' onchange="this.style.borderColor=\'red\';"';
		$snmp_result=$this->OLT_get_param($OLT_id);
		$profiles = $this->OLT_GetProfiles();

		$result='Dane z dnia: <b>'.date('Y-m-d H:i:s').'</b><br /><br />';

		if(is_array($snmp_result) && count($snmp_result)>0)
		{
			$result.='
			<FORM ID="oltedit" name="oltedit" METHOD="POST" ACTION="?m=gponoltedit&id='.$id.'">
			<input type="hidden" name="snmpsend" id="snmpsend" value="1" />
			<input type="hidden" name="save" id="save" value="1" />
			<table cellspacing="3" cellpadding="1" border="1" width="99%" rules="none">';

			$index=$this->walk('sleGponOnuAuthIndex');
			if(is_array($index) && count($index)>0)
			{
				$num=0;
				foreach($index as $k=>$v)
				{
					$num=intval($this->clean_snmp_value($v));
					$result .= '<tr><td><nobr>Radius Server '.$num.'</nobr></td>';
					$result .= '<td><nobr>Usuń <input type="checkbox" name="radiusid_'.$num.'" value="1">'.$this->get('sleGponOnuAuthAddress.'.$num).':'.$this->get('sleGponOnuAuthPort.'.$num).'&nbsp;&nbsp'.$this->get('sleGponOnuAuthKey.'.$num).' </nobr></td></tr>';
				}
			}
			$result .= '<tr><td>Dodaj serwer radius</td>
			<td><nobr><input onmouseover="popup(\'Adres IP serwera\')" onmouseout="pophide()" type="text" name="olt_radiusAddress" id="olt_radiusAddress" size="15" '.$onchange.' />';
			$result .= '<input onmouseover="popup(\'Port (domyślnie 1812)\')" onmouseout="pophide()" type="text" name="olt_radiusPort" id="olt_radiusPort" size="5" '.$onchange.' />';
			$result .= '<input onmouseover="popup(\'Secret Key\')" onmouseout="pophide()" type="text" name="olt_radiusKey" id="olt_radiusKey" size="10" '.$onchange.' /></nobr></td></tr>';

	
			$result .= '<tr><td><b>Radius Username type:</b></td><td>
			    <select name="olt_radiususernametype" '.$onchange.'>
			    <option value="1" ';
			if($snmp_result['Radius Username type'] == 'serialNumber(1)')
			{
				$result .= 'selected="selected"';
			}
			$result .= '>Serial Number</option><option value="2" ';
			if($snmp_result['Radius Username type'] == 'modelName(2)')
			{
				$result .= 'selected="selected"';
			}
			$result .= '>Model Name</option>
			    </select></td></tr>';

			$result .= '<tr><td><b>Service Profile</b></td><td>
			    <select name="serviceProfile" '.$onchange.'>
                        <option value="">-- wybierz/usuń --</option>';
			foreach($profiles as $p)
			{
				$result .= '<option value="'.$p.'"';
				if ($snmp_result['Service Profile'] == $p)
				{
					$result .= ' selected="selected"';
				}
				$result .= '>'.$p.'</option>';
			}
			$result.= '</select></td></tr></table>';
		}

		$models = $this->GPON->GetGponOnuModelsList();
		$profiles = $this->GPON_get_profiles();
		$snmp_profile = $this->OLT_GetServiceProfiles();

		$result.='<br /><table cellspacing="3" cellpadding="1" border="1" width="99%" rules="rows">
		    <tr class="dark"><td><b>Model</b></td><td><b>Service Profile</b></td></tr>';
		foreach($models as $m)
		{
		    if (is_array($m))
		    {
			$result .= '<tr><td>'.$m['name'].'</td><td><select name="modelProfile_'.$m['name'].'" '.$onchange.'>
			    <option value="">-- wybierz/usuń --</option>';
			foreach($profiles as $p)
			{
				$result.='<option value="'.$p.'" ';
				if($snmp_profile[$m['name']] == $p)
				{
					$result.='selected="selected"';
				}
				$result.=' >'.$p.'</option>';
			}
			$result .= '</td></tr>';
		    }
		}
		$result.= '</table>';

		$autoupgrade_time=$this->OLT_get_autoupgrade_times();
		if(is_array($autoupgrade_time) && count($autoupgrade_time)>0)
		{
			$result.='<br /><table cellspacing="3" cellpadding="1" border="1" width="99%" rules="rows">
				<tr class="dark"><td colspan="5" align="center"> Czas auto-upgrade</td></tr>
				<tr><td><b> Usuń </b></td> <td><b>Model </b></td><td><b>Start</b></td><td><b>Stop</b></td><td><b>Reboot</b></td></tr>';
			foreach($autoupgrade_time as $k=>$v)
			{
				if($k=='Model')
				{
					if(is_array($v) && count($v)>0)
					{
						foreach($v as $k1=>$v1)
						{
						    $idx = str_replace($this->path_OID.'sleGponOnuFWAutoUpgradeMTimeModelName.','',$k1);
						    $modelname = $this->clean_snmp_value($autoupgrade_time['Model'][$this->path_OID.'sleGponOnuFWAutoUpgradeMTimeModelName.'.$idx]);
						    $result .= '<tr><td><input type="checkbox" name="autotime_'.$modelname.'" value="1"></td>
							<td>'.$modelname.'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_time['Start'][$this->path_OID.'sleGponOnuFWAutoUpgradeMTimeStartTime.'.$idx]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_time['Stop'][$this->path_OID.'sleGponOnuFWAutoUpgradeMTimeEndTime.'.$idx]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_time['Reboot'][$this->path_OID.'sleGponOnuFWAutoUpgradeMTimeRebootTime.'.$idx]).'</td></tr>';
						}
					}
				}
			}
			$result .= '<tr><td>Nowy czas </td>
			    <td><input onmouseover="popup(\'Nazwa modelu\')" onmouseout="pophide()" type="text" name="new_autotime_ModelName" id="new_autotime_ModelName" size="15" '.$onchange.' /></td>
			    <td><select onmouseover="popup(\'Start time\')" onmouseout="pophide()" name="new_autotime_Start" '.$onchange.' >
				    <option value="-1">disable</option>';
				for ($i=0; $i < 24; $i++)
				{
					$result .= '<option value="'.$i.'">'.$i.' h</option>';
				}
			$result .= '</select></td>
			    <td><select onmouseover="popup(\'Stop time\')" onmouseout="pophide()" name="new_autotime_Stop" '.$onchange.' >
				    <option value="-1">disable</option>';
				for ($i=0; $i < 24; $i++)
				{
					$result .= '<option value="'.$i.'">'.$i.' h</option>';
				}
			$result .= '</select></td>
			    <td><select onmouseover="popup(\'Reboot time\')" onmouseout="pophide()" name="new_autotime_Reboot"  '.$onchange.' >
				    <option value="-1">disable</option>
				    <option value="24">immediately</option>';
				for ($i=0; $i < 24; $i++)
				{
					$result .= '<option value="'.$i.'">'.$i.' h</option>';
				}
			$result .= '</select></td>
			    </td></tr> </table>';
		}

		$autoupgrade_array=$this->OLT_get_autoupgrade_array();
		if(is_array($autoupgrade_array) && count($autoupgrade_array)>0)
		{

			$result.='<br /><table cellspacing="3" cellpadding="1" border="1" width="99%" rules="rows">
			    <tr class="dark"><td colspan="8" align="center"> Auto-upgrade firmware</td></tr>
			    <tr><td><b> Usuń </b></td> <td><b>Model </b></td><td><b>FW</b></td><td><b>Serwer ftp</b></td><td><b>User</b></td><td><b>Passwd</b></td><td><b>Wersja</b></td><td><b>Wyklucz</b></td></tr>';
			foreach($autoupgrade_array as $k=>$v)
			{
				if($k=='Model')
				{
					if(is_array($v) && count($v)>0)
					{
						foreach($v as $k1=>$v1)
						{
						    $idx = str_replace($this->path_OID.'sleGponOnuFWAutoUpgradeModelName.','',$k1);
						    $modelname = $this->clean_snmp_value($autoupgrade_array['Model'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelName.'.$idx]);
						    $result .= '<tr><td><input type="checkbox" name="autoupgrademodel_'.$modelname.'" value="1"></td>
							<td>'.$modelname.'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['FWName'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelFWName.'.$idx]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['Address'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelServerAddress.'.$idx]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['User'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelUser.'.$idx]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['Passwd'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelPasswd.'.$idx]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['TargetVersion'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelFWTargetVersion.'.$idx]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['Exclude'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelExclude.'.$idx]).'</td></tr>';
						}
					}
				}
			}
			$result .= '<tr><td>Nowy: </td>
			    <td><input onmouseover="popup(\'Nazwa modelu\')" onmouseout="pophide()" type="text" name="new_autoupgrade_ModelName" id="new_autoupgrade_ModelName" size="7" '.$onchange.' /></td>
			    <td><input onmouseover="popup(\'Nazwa pliku z firmwarem\')" onmouseout="pophide()" type="text" name="new_autoupgrade_FW" id="new_autoupgrade_FW" size="25" '.$onchange.' /></td>
			    <td><input onmouseover="popup(\'Adres serwera ftp\')" onmouseout="pophide()" type="text" name="new_autoupgrade_address" id="new_autoupgrade_address" size="10" '.$onchange.' /></td>
			    <td><input onmouseover="popup(\'Nazwa użytkownika ftp\')" onmouseout="pophide()" type="text" name="new_autoupgrade_user" id="new_autoupgrade_user" size="7" '.$onchange.' /></td>
			    <td><input onmouseover="popup(\'Hasło ftp\')" onmouseout="pophide()" type="text" name="new_autoupgrade_passwd" id="new_autoupgrade_passwd" size="8" '.$onchange.' /></td>
			    <td><input onmouseover="popup(\'Wersja\')" onmouseout="pophide()" type="text" name="new_autoupgrade_version" id="new_autoupgrade_version" size="8" '.$onchange.' /></td>
			    </select></td>
			    <td><select onmouseover="popup(\'Aktualizuje tę wersją / wyklucz z aktualizacji tę wersję\')" onmouseout="pophide()" name="new_autoupgrade_exclude">
				    <option value="1">Wyklucz</option>
				    <option value="2">tę wersję</option>
			    </select></td> </tr></table>';
		}

		$param_array=$this->OLT_get_param_array();
		if(is_array($param_array) && count($param_array)>0)
		{
			$result.='<br /><table cellspacing="3" cellpadding="1" border="1" width="99%" rules="rows">';
			$result.='<tr class="dark"><td><b>Port OLT</b></td>
			    <td onmouseover="popup(\'Automatyczne usuwanie ONT z OLT, gdy będzie NIEAKTYWNE przez zdefiniowaną liczbę dni, 0 - wyłączone\')" onmouseout="pophide()"><b>Aging Time</b></td>
			    <td><b>Auto Upgrade</b></td>
			    <td><b>Radius Auth</b></td></tr>';

			foreach($param_array as $k=>$v)
			{
				if($k=='Status')
				{
					if(is_array($v) && count($v)>0)
					{
						foreach($v as $k1=>$v1)
						{
							$numport=str_replace($this->path_OID.'sleGponOltStatus.','',$k1);
							$result .= '<tr><td>'.$numport.'</td><td><select name="aging_'.$numport.'" '.$onchange.'>';
							for ($i=0; $i < 31; $i++)
							{
							    $result .= '<option value="'.$i.'"';
							    if ($this->clean_snmp_value($param_array['OnuAgingTime'][$this->path_OID.'sleGponOltOnuInactiveAgingTime.'.$numport]) == $i)
							    {
								    $result .= ' selected="selected" ';
							    }
							    $result .= '>'.$i.'</option>';
							}
							$result .= '</select></td><td><select name="autoupgrade_'.$numport.'" '.$onchange.'>';
							// onu auto-upgrade enable/disable
							$result .= '<option value="1" ';
							if ($this->clean_snmp_value($param_array['AutoUpgrade'][$this->path_OID.'sleGponOnuFWAutoUpgradeMode.'.$numport]) == 'enable(1)')
							{
							    $result .= ' selected="selected" ';
							}
							$result .= '>enable</option> <option value="2" ';
							if ($this->clean_snmp_value($param_array['AutoUpgrade'][$this->path_OID.'sleGponOnuFWAutoUpgradeMode.'.$numport]) == 'disable(2)')
							{
							    $result .= ' selected="selected" ';
							}
							$result .= '>disable</option></select></td> <td><select name="authmode_'.$numport.'" '.$onchange.'>';
							// onu auth-control enable/disable
							$result .= '<option value="1" ';
							if ($this->clean_snmp_value($param_array['AuthControlMode'][$this->path_OID.'sleGponOltOnuAuthControlMode.'.$numport]) == 'enable(1)')
							{
							    $result .= ' selected="selected" ';
							}
							$result .= '>enable</option> <option value="0" ';
							if ($this->clean_snmp_value($param_array['AuthControlMode'][$this->path_OID.'sleGponOltOnuAuthControlMode.'.$numport]) == 'disable(0)')
							{
							    $result .= ' selected="selected" ';
							}
							$result .= '>disable</option></select></td><tr>';
						}
					}
				}
			}
		}

		$result .= '<tr><td align="right" colspan="4"><input type="button" value="Zapisz zmiany na OLT" id="save_changes" OnClick="document.getElementById(\'save\').value=1;this.form.submit();" /></td></tr>
			</table>
			</form>';

		return $result;
	}

	function OLT_get_param($OLT_id=0)
	{
		$result=array();
		$result['Version']=$this->get('sysDescr.0','SNMPv2-MIB::');
		$result['Up time']=$this->get('sysUpTimeInstance','DISMAN-EVENT-MIB::');
		$result['Contact']=$this->get('sysContact.0','SNMPv2-MIB::');
		$result['Name']=$this->get('sysName.0','SNMPv2-MIB::');
		$result['Location']=$this->get('sysLocation.0','SNMPv2-MIB::');	
		$result['OLT ID']=$OLT_id;
		$PowerState=$this->walk('slePowerState','SLE-DEVICE-MIB::');
		if(is_array($PowerState) && count($PowerState)>0)
		{
			foreach($PowerState as $k=>$v)
			{
				if($this->clean_snmp_value($v)=='ok(1)')
				{
					$PowerS[]='<img src="img/' . LMSGponDasanPlugin::plugin_directory_name . '/green.png" /> (ok)';
				}
				else 
				{
					$PowerS[]='<img src="img/' . LMSGponDasanPlugin::plugin_directory_name . '/red.png" /> (fail)';
				}
			}
			if(is_array($PowerS) && count($PowerS)>0)
			{
				$result['Power State']=implode(', ',$PowerS);
			}
		}
		$FanUnitOperState=$this->walk('sleFanUnitOperState','SLE-DEVICE-MIB::');
		if(is_array($FanUnitOperState) && count($FanUnitOperState)>0)
		{
			foreach($FanUnitOperState as $k=>$v)
			{
				if($this->clean_snmp_value($v)=='ok(1)')
				{
					$FanU[]='<img src="img/' . LMSGponDasanPlugin::plugin_directory_name . '/green.png" /> (ok)';
				}
				else 
				{
					$FanU[]='<img src="img/' . LMSGponDasanPlugin::plugin_directory_name . '/red.png" /> (fail)';
				}
			}
			if(is_array($FanU) && count($FanU)>0)
			{
				$result['Fan']=implode(', ',$FanU);
			}
		}
		$FanUnitSpeed=$this->walk('sleFanUnitSpeed','SLE-DEVICE-MIB::');
		if(is_array($FanUnitSpeed) && count($FanUnitSpeed)>0)
		{
			foreach($FanUnitSpeed as $k=>$v)
			{
				$FanS[]=str_replace('SLE-DEVICE-MIB::sleFanUnitSpeed.','',$k).': '.$this->clean_snmp_value($v);
			}
			if(is_array($FanS) && count($FanS)>0)
			{
				$result['Fan speed']=implode(', ',$FanS);
			}
		}
		$TemperatureValue=$this->walk('sleTemperatureValue','SLE-DEVICE-MIB::');
		if(is_array($TemperatureValue) && count($TemperatureValue)>0)
		{
			foreach($TemperatureValue as $k=>$v)
			{
				$TempV[]=str_replace('SLE-DEVICE-MIB::sleTemperatureValue.','',$k).': '.$this->clean_snmp_value($v).' &deg;C';
			}
			if(is_array($TempV) && count($TempV)>0)
			{
				$result['Temp']=implode(', ',$TempV);
			}
		}
		
		$result['CPU Load All']=$this->get('sleSystemCPULoadAll','SLE-SYSTEMMAINTENANCE-MIB::');	
		$result['CPU Load Interrupt']=$this->get('sleSystemCPULoadInterrupt','SLE-SYSTEMMAINTENANCE-MIB::');	
		$result['System Memory Total']=$this->get('sleSystemMemoryTotal','SLE-SYSTEMMAINTENANCE-MIB::');	
		$result['System Memory Free']=$this->get('sleSystemMemoryFree','SLE-SYSTEMMAINTENANCE-MIB::');	

		$index=$this->walk('sleGponOnuAuthIndex');
		if(is_array($index) && count($index)>0)
		{
			$num=0;
			foreach($index as $k=>$v)
			{
				$num=intval($this->clean_snmp_value($v));
				$serv = $this->get('sleGponOnuAuthAddress.'.$num);
				$port = $this->get('sleGponOnuAuthPort.'.$num);
				$key  = $this->get('sleGponOnuAuthKey.'.$num);
				$result['Radius Server '.$num]= $serv.':'.$port."  ".$key;
			}
		}
		$result['Radius Username type']=$this->get('sleGponOnuAuthRadiusUserName');
		$result['Service Profile']=$this->get('sleGponDefaultServiceProfile');
		
		return $result;
	}
	function OLT_get_autoupgrade_array()
	{
		$result=array();
		$result['Model'] = $this->walk('sleGponOnuFWAutoUpgradeModelName');
		$result['FWName'] = $this->walk('sleGponOnuFWAutoUpgradeModelFWName');
		$result['Status'] = $this->walk('sleGponOnuFWAutoUpgradeModelStatus');
		$result['Method'] = $this->walk('sleGponOnuFWAutoUpgradeModelMethod');
		$result['Address'] = $this->walk('sleGponOnuFWAutoUpgradeModelServerAddress');
		$result['User'] = $this->walk('sleGponOnuFWAutoUpgradeModelUser');
		$result['Passwd'] = $this->walk('sleGponOnuFWAutoUpgradeModelPasswd');
		$result['TargetVersion'] = $this->walk('sleGponOnuFWAutoUpgradeModelFWTargetVersion');
		$result['Exclude'] = $this->walk('sleGponOnuFWAutoUpgradeModelExclude');

		return $result;
	}
	function OLT_get_autoupgrade_times()
	{
		$result = array();
		$result['Model'] = $this->walk('sleGponOnuFWAutoUpgradeMTimeModelName');
		$result['Start'] = $this->walk('sleGponOnuFWAutoUpgradeMTimeStartTime');
		$result['Stop'] = $this->walk('sleGponOnuFWAutoUpgradeMTimeEndTime');
		$result['Reboot'] = $this->walk('sleGponOnuFWAutoUpgradeMTimeRebootTime');

		return $result;
	}
	function OLT_get_param_array()
	{
		$result=array();
		$result['Status']=$this->walk('sleGponOltStatus','SLE-GPON-MIB::');
		$result['Protection']=$this->walk('sleGponOltProtection','SLE-GPON-MIB::');
		$result['Max Distance']=$this->walk('sleGponOltLinkMaxDistance','SLE-GPON-MIB::');
		$result['Fec Mode DS']=$this->walk('sleGponOltFecModeDS','SLE-GPON-MIB::');
		$result['Fec Mode US']=$this->walk('sleGponOltFecModeUS','SLE-GPON-MIB::');
		$result['Moc Tx']=$this->walk('sleEthernetPortDMITxPower','SLE-DEVICE-MIB::');
		$result['ActiveOnu']=$this->walk('sleGponOltActiveOnuCount');
		$result['InactiveOnu']=$this->walk('sleGponOltInactiveOnuCount');
		$result['OnuAgingTime']=$this->walk('sleGponOltOnuInactiveAgingTime');
		$result['AutoUpgrade']=$this->walk('sleGponOnuFWAutoUpgradeMode');
		$result['AuthControlMode']=$this->walk('sleGponOltOnuAuthControlMode');
		
		return $result;
	}
	function OLT_get_param_table($OLT_id=0)
	{
		$result='';
		$snmp_result=$this->OLT_get_param($OLT_id);
		$snmp_profiles = $this->OLT_GetServiceProfiles();
		$result='Dane z dnia: <b>'.date('Y-m-d H:i:s').'</b><br /><br />';
		if(is_array($snmp_result) && count($snmp_result)>0)
		{
			$result.='<table cellpadding="3" border="1">';
			foreach($snmp_result as $k=>$v)
			{
				$result.='<tr><td>'.$k.':</td><td align="right"><b>'.$v.'</b></td></tr>';
			}
			if(is_array($snmp_profiles) && count($snmp_profiles)>0)
			{
				$result.='<tr><td colspan="2" align="center"><b>Service Profiles</b></td></tr>';
				foreach($snmp_profiles as $m => $p)
				{
					$result.='<tr><td align="right">'.$m.'</td><td align="left"><b>'.$p.'</b></td></tr>';
				}
			}
			$result.='</table>';
		}
		$param_array=$this->OLT_get_param_array();
		if(is_array($param_array) && count($param_array)>0)
		{
			$result.='<br /><table cellpadding="3" border="1"><tr class="dark"><td colspan="40" align="center"> OLT ports </td></tr>';
			$result.='<tr><td><b>Port</b></td><td><b>Status</b></td>
			    <td onmouseover="popup(\'Protection\')" onmouseout="pophide()"><b>Prot.</b></td>
			    <td onmouseover="popup(\'Max Distance\')" onmouseout="pophide()"><b>Max Dist.</b></td>
			    <td><b>Fec Mode DS/US</b></td><td><b>Moc Tx</b></td>
			    <td onmouseover="popup(\'Liczba aktywnych / niekatywnych onu na porcie\')" onmouseout="pophide()"><b>Act/Inact Onu</b></td>
			    <td onmouseover="popup(\'Automatyczne usuwanie ONT z OLT, gdy będzie NIEAKTYWNE przez zdefiniowaną liczbę dni, 0 - wyłączone\')" onmouseout="pophide()"><b>Aging Time</b></td>
			    <td onmouseover="popup(\'AutoUpgrade\')" onmouseout="pophide()"><b>Auto Upgrade</b></td>
			    <td onmouseover="popup(\'Autentykacja poprzez serwer radius\')" onmouseout="pophide()"><b>Auth radius</b></td>';
			foreach($param_array as $k=>$v)
			{
				if($k=='Status')
				{
					if(is_array($v) && count($v)>0)
					{
						foreach($v as $k1=>$v1)
						{
							$numport=str_replace($this->path_OID.'sleGponOltStatus.','',$k1);
							$result.='<tr>
							<td>'.$numport.'</td>
							<td>'.$this->clean_snmp_value($v1).'</td>
							<td>'.$this->clean_snmp_value($param_array['Protection'][$this->path_OID.'sleGponOltProtection.'.$numport]).'</td>
							<td>'.$this->clean_snmp_value($param_array['Max Distance'][$this->path_OID.'sleGponOltLinkMaxDistance.'.$numport]).' Km</td>
							<td>'.$this->clean_snmp_value($param_array['Fec Mode DS'][$this->path_OID.'sleGponOltFecModeDS.'.$numport]).'/'.$this->clean_snmp_value($param_array['Fec Mode US'][$this->path_OID.'sleGponOltFecModeUS.'.$numport]).'</td>
							<td>'.$this->clean_snmp_value($param_array['Moc Tx']['SLE-DEVICE-MIB::'.'sleEthernetPortDMITxPower.'.$numport]).'</td>
							<td>'.$this->clean_snmp_value($param_array['ActiveOnu'][$this->path_OID.'sleGponOltActiveOnuCount.'.$numport]).' / '.$this->clean_snmp_value($param_array['InactiveOnu'][$this->path_OID.'sleGponOltInactiveOnuCount.'.$numport]).'</td>
							<td>'.$this->clean_snmp_value($param_array['OnuAgingTime'][$this->path_OID.'sleGponOltOnuInactiveAgingTime.'.$numport]).'</td>
							<td>'.$this->clean_snmp_value($param_array['AutoUpgrade'][$this->path_OID.'sleGponOnuFWAutoUpgradeMode.'.$numport]).'</td>
							<td>'.$this->clean_snmp_value($param_array['AuthControlMode'][$this->path_OID.'sleGponOltOnuAuthControlMode.'.$numport]).'</td>
							</tr>';
						}
					}
				}
			}
			$result.='</table>';
		}

		//auto upgrade params
		$autoupgrade_time=$this->OLT_get_autoupgrade_times();
		if(is_array($autoupgrade_time) && count($autoupgrade_time)>0)
		{
			$result.='<br /><table cellpadding="3" border="1"><tr class="dark"><td colspan="4" align="center"> AutoUpgrade Time </td></tr>';
			$result.='<tr><td><b>Model</b></td><td><b>Start</b></td>
			    <td><b>Stop</b></td>
			    <td><b>Reboot Time</b></td>';
			foreach($autoupgrade_time as $k=>$v)
			{
				if($k=='Model')
				{
					if(is_array($v) && count($v)>0)
					{
						foreach($v as $k1=>$v1)
						{
							$num=str_replace($this->path_OID.'sleGponOnuFWAutoUpgradeMTimeModelName.','',$k1);
							$result.='<tr>
							<td>'.$this->clean_snmp_value($v1).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_time['Start'][$this->path_OID.'sleGponOnuFWAutoUpgradeMTimeStartTime.'.$num]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_time['Stop'][$this->path_OID.'sleGponOnuFWAutoUpgradeMTimeEndTime.'.$num]).'</td>';
							$reboottime = $this->clean_snmp_value($autoupgrade_time['Reboot'][$this->path_OID.'sleGponOnuFWAutoUpgradeMTimeRebootTime.'.$num]);
							if($reboottime == 24)
							    $reboottime .= ' - immediately';
							if($reboottime == -1)
							    $reboottime .= ' - unused';

							$result.= '<td>'.$reboottime.' h</td>';
						}
					}
				}
			}
			$result.='</table>';
		}
		
		$autoupgrade_array=$this->OLT_get_autoupgrade_array();
		if(is_array($autoupgrade_array) && count($autoupgrade_array)>0)
		{
			$result.='<br /><table cellpadding="3" border="1"><tr class="dark"><td colspan="8" align="center"> AutoUpgrade </td></tr>';
			$result.='<tr><td><b>Model</b></td><td><b>Status</b></td>
			    <td><b>Metoda</b></td>
			    <td><b>Adres serwera</b></td>
			    <td><b>Login</b></td>
			    <td><b>Nazwa pliku firmware</b></td>
			    <td><b>Wersja</b></td>
			    <td><b>Wyklucz</b></td>';
			foreach($autoupgrade_array as $k=>$v)
			{
				if($k=='Model')
				{
					if(is_array($v) && count($v)>0)
					{
						foreach($v as $k1=>$v1)
						{
							$num=str_replace($this->path_OID.'sleGponOnuFWAutoUpgradeModelName.','',$k1);
							$result.='<tr>
							<td>'.$this->clean_snmp_value($v1).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['Status'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelStatus.'.$num]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['Method'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelMethod.'.$num]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['Address'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelServerAddress.'.$num]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['User'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelUser.'.$num]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['FWName'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelFWName.'.$num]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['TargetVersion'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelFWTargetVersion.'.$num]).'</td>
							<td>'.$this->clean_snmp_value($autoupgrade_array['Exclude'][$this->path_OID.'sleGponOnuFWAutoUpgradeModelExclude.'.$num]).'</td>';

						}
					}
				}
			}
			$result.='</table>';
		}
	
		return $result;
	}
	function ONU_Reset($OLT_id,$ONU_id)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuControlRequest','i',8);
				$result[]=$this->set('sleGponOnuControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuControlId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuControlTimer','u',0);
				$this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Onu Reset '.$ONU_id.', olt '.$OLT_id);
			}
		}
		return array_unique($result);
	}
	function ONU_ClearMac($OLT_id,$ONU_id)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuUserMacControlRequest','i',2);
				$result[]=$this->set('sleGponOnuUserMacControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuUserMacControlOnuId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuUserMacControlTimer','u',0);
				$this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Onu Clear Mac '.$ONU_id.', olt '.$OLT_id);
			}
		}
		return array_unique($result);
	}
	function ONU_Status($OLT_id,$ONU_id,$status)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$status=intval($status);
		if($OLT_id>0 && $ONU_id>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuControlRequest','i',14);
				$result[]=$this->set('sleGponOnuControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuControlId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuControlActiveStatus','i',$status);
				$result[]=$this->set('sleGponOnuControlTimer','u',0);
			}
		}
		return array_unique($result);
	}
	function ONU_SetPortStatus($OLT_id,$ONU_id,$typ,$port,$status)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$typ=intval($typ);
		$port=intval($port);
		$status=intval($status);
		if($OLT_id>0 && $ONU_id>0 && $port>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuPortControlRequest','i',1);
				$result[]=$this->set('sleGponOnuPortControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuPortControlOnuId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuPortControlSlotId','i',$typ);
				$result[]=$this->set('sleGponOnuPortControlPortId','i',$port);
				$result[]=$this->set('sleGponOnuPortControlAdminStatus','i',$status);
				$result[]=$this->set('sleGponOnuPortControlTimer','u',0);
				//echo'$OLT_id='.$OLT_id.', $ONU_id='.$ONU_id.', $typ='.$typ.', $port='.$port.', $status='.$status;
			}
		}
		return array_unique($result);
	}
	function ONU_SetAutoNego($OLT_id,$ONU_id,$typ,$port,$autonego)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$typ=intval($typ);
		$port=intval($port);
		$autonego=intval($autonego);
		if($OLT_id>0 && $ONU_id>0 && $port>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuPortControlRequest','i',2);
				$result[]=$this->set('sleGponOnuPortControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuPortControlOnuId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuPortControlSlotId','i',$typ);
				$result[]=$this->set('sleGponOnuPortControlPortId','i',$port);
				$result[]=$this->set('sleGponOnuPortControlAutoNego','i',$autonego);
				$result[]=$this->set('sleGponOnuPortControlTimer','u',0);
				//echo'$OLT_id='.$OLT_id.', $ONU_id='.$ONU_id.', $typ='.$typ.', $port='.$port.', $autonego='.$autonego;
			}
		}
		return array_unique($result);
	}
	function ONU_SetMediumMode($OLT_id,$ONU_id,$typ,$port,$mediummode)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$typ=intval($typ);
		$port=intval($port);
		$mediummode=intval($mediummode);
		if($OLT_id>0 && $ONU_id>0 && $port>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuPortControlRequest','i',7);
				$result[]=$this->set('sleGponOnuPortControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuPortControlOnuId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuPortControlSlotId','i',$typ);
				$result[]=$this->set('sleGponOnuPortControlPortId','i',$port);
				$result[]=$this->set('sleGponOnuPortControlMediumMode','i',$mediummode);
				$result[]=$this->set('sleGponOnuPortControlTimer','u',0);
				//echo'$OLT_id='.$OLT_id.', $ONU_id='.$ONU_id.', $typ='.$typ.', $port='.$port.', $mediummode='.$mediummode;
			}
		}
		return array_unique($result);
	}
	function ONU_SetPortSpeed($OLT_id,$ONU_id,$typ,$port,$speed,$duplex=1)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$typ=intval($typ);
		$port=intval($port);
		$speed=intval($speed);
		$duplex=intval($duplex);
		if($OLT_id>0 && $ONU_id>0 && $port>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuPortControlRequest','i',6);
				$result[]=$this->set('sleGponOnuPortControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuPortControlOnuId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuPortControlSlotId','i',$typ);
				$result[]=$this->set('sleGponOnuPortControlPortId','i',$port);
				$result[]=$this->set('sleGponOnuPortControlSpeed','i',$speed);
				$result[]=$this->set('sleGponOnuPortControlDuplex','i',$duplex);
				$result[]=$this->set('sleGponOnuPortControlTimer','u',0);
				//echo'$OLT_id='.$OLT_id.', $ONU_id='.$ONU_id.', $typ='.$typ.', $port='.$port.', $speed='.$speed;
			}
		}
		return array_unique($result);
	}
	function ONU_SetPhoneVoip($OLT_id,$ONU_id,$typ,$port,$phone_data=array())
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$typ=intval($typ);
		$port=intval($port);
		if($OLT_id>0 && $ONU_id>0 && $port>0 && is_array($phone_data))
		{
			if(count($phone_data)==0)
			{
				$phone_data['login']='';
				$phone_data['passwd']='';
				$phone_data['phone']='';
			}
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponProfileVoIPOmciControlRequest','i',1);
				$result[]=$this->set('sleGponProfileVoIPOmciControlOltIndex','i',$OLT_id);
				$result[]=$this->set('sleGponProfileVoIPOmciControlOnuIndex','i',$ONU_id);
				$result[]=$this->set('sleGponProfileVoIPOmciControlUniId','i',$port);
				$result[]=$this->set('sleGponProfileVoIPOmciControlAuthName','s',$phone_data['login']);
				$result[]=$this->set('sleGponProfileVoIPOmciControlAuthPasswd','s',$phone_data['passwd']);
				$result[]=$this->set('sleGponProfileVoIPOmciControlTimer','u',0);
				
				$result[]=$this->set('sleGponProfileVoIPOmciControlRequest','i',2);
				$result[]=$this->set('sleGponProfileVoIPOmciControlOltIndex','i',$OLT_id);
				$result[]=$this->set('sleGponProfileVoIPOmciControlOnuIndex','i',$ONU_id);
				$result[]=$this->set('sleGponProfileVoIPOmciControlUniId','i',$port);
				$result[]=$this->set('sleGponProfileVoIPOmciControlPhoneNumber','s',$phone_data['phone']);
				$result[]=$this->set('sleGponProfileVoIPOmciControlTimer','u',0);
			}
		}
		return array_unique($result);
	}
	function ONU_SetHostIp($OLT_id,$ONU_id,$hostid,$ip,$gw)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$hostid=intval($hostid);
		$ip=trim($ip);
		$gw=trim($gw);
		list($ipaddr,$mask) = preg_split('/\//', $ip);
		if($OLT_id>0 && $ONU_id>0 && $hostid>0 && strlen($ipaddr)>6 && strlen($gw)>6 && strlen($mask)>0)
		{

			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuHostControlRequest','i',1);
				$result[]=$this->set('sleGponOnuHostControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuHostControlOnuId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuHostControlId','i',$hostid);
				$result[]=$this->set('sleGponOnuHostControlIPAddr','a',$ipaddr);
				$result[]=$this->set('sleGponOnuHostControlMask','a',$mask);
				$result[]=$this->set('sleGponOnuHostControlGW','a',$gw);
				$result[]=$this->set('sleGponOnuHostControlTimer','u',0);
			}
		}
		return array_unique($result);
	}
	function GPON_get_profiles()
	{
		$result=array();
		$profiles=$this->walk('sleGponProfileName');
		if(is_array($profiles) && count($profiles)>0)
		{
			foreach($profiles as $k=>$v)
			{
				$v=$this->clean_snmp_value($v);
				$result[$v]=$v;
			}
		}
		return $result;
	}

	function ONU_SetProfile($OLT_id,$ONU_id,$profile)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$profile=trim($profile);
		if($OLT_id>0 && $ONU_id>0 && strlen($profile)>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{	
				$result[]=$this->set('sleGponOnuControlRequest','i',3);
				$result[]=$this->set('sleGponOnuControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuControlId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuControlProfile','s',$profile);
				$result[]=$this->set('sleGponOnuControlTimer','u',0);
				//echo'$OLT_id='.$OLT_id.', $ONU_id='.$ONU_id.', $profile='.$profile;
			}
		}
		return array_unique($result);
	}

	function ONU_SetAccount($OLT_id,$ONU_id,$username,$password)
	{
		$result=array();
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$username=trim($username);
		$password=trim($password);
		if($OLT_id>0 && $ONU_id>0 && strlen($username)>0 && strlen($password)>0)
		{
			$onu=$this->walk('sleGponOnuSerial');
			if($this->search_array_key($onu,'sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id))
			{
				$result[]=$this->set('sleGponOnuControlRequest','i',26); //setOnuAccount(26), updateOnuAccount(25)
				$result[]=$this->set('sleGponOnuControlOltId','i',$OLT_id);
				$result[]=$this->set('sleGponOnuControlId','i',$ONU_id);
				$result[]=$this->set('sleGponOnuControlAccountUserName','s',$username);
				$result[]=$this->set('sleGponOnuControlAccountPassword','s',$password);
				$result[]=$this->set('sleGponOnuControlTimer','u',0);
			}
		}
		return array_unique($result);
	}
	
	function ONU_is_real($OLT_id,$ONU_id,$ONU_name)
	{
		$result=false;
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		$ONU_name=trim($ONU_name);
		if($OLT_id>0 && $ONU_id>0 && strlen($ONU_name)>0)
		{
			$onu=$this->clean_snmp_value($this->get('sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id));
			if($onu==$ONU_name)
			{
				$result=true;
			}
		}
		return $result;
	}
	function ONU_GetSerial($OLT_id,$ONU_id)
	{
		$result='';
		$OLT_id=intval($OLT_id);
		$ONU_id=intval($ONU_id);
		if($OLT_id>0 && $ONU_id>0)
		{
			$result=$this->clean_snmp_value($this->get('sleGponOnuSerial.'.$OLT_id.'.'.$ONU_id));
		}
		return $result;
	}
	function OLT_GetServiceProfiles()
	{
		$result=array();
		$result_temp=$this->walk('sleGponServiceProfileIndex');
		if(is_array($result_temp) && count($result_temp)>0)
		{
			foreach($result_temp as $k=>$v)
			{
				$num = $this->clean_snmp_value($v);
				$result[$this->clean_snmp_value($this->get('sleGponServiceProfileModelName.'.$num))]=$this->clean_snmp_value($this->get('sleGponServiceProfileProfileName.'.$num));
			}
		}
		return $result;
	}
	function OLT_GetTrafficProfiles()
	{
		$result=array();
		$result_temp=$this->walk('sleGponProfile2TrafficName');
		if(is_array($result_temp) && count($result_temp)>0)
		{
			foreach($result_temp as $k=>$v)
			{
				$result[$this->clean_snmp_value($v)]=$this->clean_snmp_value($v);
			}
		}
		return $result;
	}
	function OLT_GetProfiles()
	{
		$result=array();
		$result_temp=$this->walk('sleGponProfileName');
		if(is_array($result_temp) && count($result_temp)>0)
		{
			foreach($result_temp as $k=>$v)
			{
				$k=str_replace($this->path_OID.'sleGponProfileName.','',$k);
				$result[$k]=$this->clean_snmp_value($v);
			}
		}
		return $result;
	}
	function OLT_AddProfile($profile_name,$traffic_profile_name)
	{
		$result=array();
		$profile_name=trim($profile_name);
		$traffic_profile_name=trim($traffic_profile_name);
		if(strlen($profile_name)>0 && strlen($traffic_profile_name)>0)
		{
			$result[]=$this->set('sleGponProfile2OnuControlRequest','i',1);
			$result[]=$this->set('sleGponProfile2OnuControlName','s',$profile_name);
			$result[]=$this->set('sleGponProfile2OnuControlTimer','u',0);
			
			$result[]=$this->set('sleGponProfile2OnuControlRequest','i',5);
			$result[]=$this->set('sleGponProfile2OnuControlName','s',$profile_name);
			$result[]=$this->set('sleGponProfile2OnuControlTrafficProfile','s',$traffic_profile_name);
			$result[]=$this->set('sleGponProfile2OnuControlTimer','u',0);
			$this->GPON->Log(4, 'SNMP gponolt', $this->options['id'], 'Profile added '.$profile_name);
		}
		return array_unique($result);
	}
	
	function OLT_ModifyProfile($profile_name,$eth,$sir,$vid,$cos,$status)
	{
		$result=array();
		$profile_name=trim($profile_name);
		$eth=intval($eth);
		$sir=intval($sir);
		$vid=intval($vid);
		$cos=intval($cos);
		$status=intval($status);
		if(strlen($profile_name)>0 && $eth>0 && $status>0)
		{
			//uni eth 1 rate-limit downstream 64 64
			$result[]=$this->set('sleGponProfile2OnuUniControlRequest','i',1);
			$result[]=$this->set('sleGponProfile2OnuUniControlOnuProfile','s',$profile_name);
			$result[]=$this->set('sleGponProfile2OnuUniControlId','i',$eth);
			$result[]=$this->set('sleGponProfile2OnuUniControlDownSir','i',$sir);
			$result[]=$this->set('sleGponProfile2OnuUniControlDownPir','i',$sir);
			$result[]=$this->set('sleGponProfile2OnuUniControlTimer','u',0);
			
			//uni eth 1 vlan-operation ds-oper remove
			$result[]=$this->set('sleGponProfile2OnuUniControlRequest','i',2);
			$result[]=$this->set('sleGponProfile2OnuUniControlOnuProfile','s',$profile_name);
			$result[]=$this->set('sleGponProfile2OnuUniControlId','i',$eth);
			$result[]=$this->set('sleGponProfile2OnuUniControlVlanDsOper','i',4);
			$result[]=$this->set('sleGponProfile2OnuUniControlTimer','u',0);
			
			//uni eth 1 vlan-operation us-oper overwrite 3013 0
			$result[]=$this->set('sleGponProfile2OnuUniControlRequest','i',3);
			$result[]=$this->set('sleGponProfile2OnuUniControlOnuProfile','s',$profile_name);
			$result[]=$this->set('sleGponProfile2OnuUniControlId','i',$eth);
			$result[]=$this->set('sleGponProfile2OnuUniControlVlanUsOper','i',2);
			$result[]=$this->set('sleGponProfile2OnuUniControlVlanUsOperVID','i',$vid);
			$result[]=$this->set('sleGponProfile2OnuUniControlVlanUsOperCoS','i',$cos);
			$result[]=$this->set('sleGponProfile2OnuUniControlTimer','u',0);
			
			//status
			$result[]=$this->set('sleGponProfile2OnuUniControlRequest','i',5);
			$result[]=$this->set('sleGponProfile2OnuUniControlOnuProfile','s',$profile_name);
			$result[]=$this->set('sleGponProfile2OnuUniControlId','i',$eth);
			$result[]=$this->set('sleGponProfile2OnuUniControlPortAdmin','i',$status);
			$result[]=$this->set('sleGponProfile2OnuUniControlTimer','u',0);
		}
		return array_unique($result);
	}
	function OLT_GetProfilesData($profile_name)
	{
		$result=array();
		$profile_name=trim($profile_name);
		$profile=$this->walk('sleGponProfile2OnuName');
		if(strlen($profile_name)>0 && is_array($profile) && count($profile)>0)
		{
			$index=0;
			foreach($profile as $k=>$v)
			{
				if(str_replace('&nbsp;','::',$this->clean_snmp_value($v))==$profile_name)
				{
					$index=str_replace($this->path_OID.'sleGponProfile2OnuName.','',$k);
					break;
				}
			}
			if($index>0)
			{
				$result['profile_name']=str_replace('::',' ',$profile_name);
				$result['trafficprofiles']=$this->clean_snmp_value($this->get('sleGponProfile2OnuTrafficProfile.'.$index));
				$downstream=$this->walk('sleGponProfile2OnuUniDownSir.'.$index);
				if(is_array($downstream) && count($downstream)>0)
				{
					foreach($downstream as $k1=>$v1)
					{
						$k1=str_replace('.eth','',str_replace($this->path_OID.'sleGponProfile2OnuUniDownSir.'.$index.'.','',$k1));
						$result['downstream_'.$k1]=$this->clean_snmp_value($v1);
					}
				}
				$vlan_id=$this->walk('sleGponProfile2OnuUniVlanUsOperVID.'.$index);
				if(is_array($vlan_id) && count($vlan_id)>0)
				{
					foreach($vlan_id as $k1=>$v1)
					{
						$k1=str_replace('.eth','',str_replace($this->path_OID.'sleGponProfile2OnuUniVlanUsOperVID.'.$index.'.','',$k1));
						$result['vlan_id_'.$k1]=$this->clean_snmp_value($v1);
					}
				}
				$cos=$this->walk('sleGponProfile2OnuUniVlanUsOperCoS.'.$index);
				if(is_array($cos) && count($cos)>0)
				{
					foreach($cos as $k1=>$v1)
					{
						$k1=str_replace('.eth','',str_replace($this->path_OID.'sleGponProfile2OnuUniVlanUsOperCoS.'.$index.'.','',$k1));
						$result['cos_'.$k1]=$this->clean_snmp_value($v1);
					}
				}
				$status=$this->walk('sleGponProfile2OnuUniPortAdmin.'.$index);
				if(is_array($status) && count($status)>0)
				{
					foreach($status as $k1=>$v1)
					{
						$k1=str_replace('.eth','',str_replace($this->path_OID.'sleGponProfile2OnuUniPortAdmin.'.$index.'.','',$k1));
						$result['status_'.$k1]=$this->clean_snmp_value($v1);
					}
				}
			}
		}
		return $result;
	}
}
?>
