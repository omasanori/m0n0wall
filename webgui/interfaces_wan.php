#!/usr/local/bin/php
<?php 
/*
	$Id$
	part of m0n0wall (http://m0n0.ch/wall)
	
	Copyright (C) 2003-2007 Manuel Kasper <mk@neon1.net>.
	All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
	
	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.
	
	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.
	
	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

$pgtitle = array("Interfaces", "WAN");
require("guiconfig.inc");

$wancfg = &$config['interfaces']['wan'];
$optcfg = &$config['interfaces']['wan'];

$pconfig['username'] = $config['pppoe']['username'];
$pconfig['password'] = $config['pppoe']['password'];
$pconfig['provider'] = $config['pppoe']['provider'];

$pconfig['pptp_username'] = $config['pptp']['username'];
$pconfig['pptp_password'] = $config['pptp']['password'];
$pconfig['pptp_local'] = $config['pptp']['local'];
$pconfig['pptp_subnet'] = $config['pptp']['subnet'];
$pconfig['pptp_remote'] = $config['pptp']['remote'];

$pconfig['dhcphostname'] = $wancfg['dhcphostname'];

if ($wancfg['ipaddr'] == "dhcp") {
	$pconfig['type'] = "DHCP";
} else if ($wancfg['ipaddr'] == "pppoe") {
	$pconfig['type'] = "PPPoE";
} else if ($wancfg['ipaddr'] == "pptp") {
	$pconfig['type'] = "PPTP";
} else {
	$pconfig['type'] = "Static";
	$pconfig['ipaddr'] = $wancfg['ipaddr'];
	$pconfig['subnet'] = $wancfg['subnet'];
	$pconfig['gateway'] = $wancfg['gateway'];
	$pconfig['pointtopoint'] = $wancfg['pointtopoint'];
}

$pconfig['blockpriv'] = isset($wancfg['blockpriv']);
$pconfig['spoofmac'] = $wancfg['spoofmac'];

if (ipv6enabled()) {	
	if ($wancfg['ipaddr6'] == "6to4") {
		$pconfig['ipv6mode'] = "6to4";
	} else if ($wancfg['ipaddr6']) {
		$pconfig['ipaddr6'] = $wancfg['ipaddr6'];
		$pconfig['subnet6'] = $wancfg['subnet6'];
		$pconfig['gateway6'] = $wancfg['gateway6'];
		$pconfig['ipv6mode'] = "static";
	} else {
		$pconfig['ipv6mode'] = "disabled";
	}
}

/* Wireless interface? */
if (isset($optcfg['wireless'])) {
	require("interfaces_wlan.inc");
	wireless_config_init();
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['type'] == "Static") {
		$reqdfields = explode(" ", "ipaddr subnet gateway");
		$reqdfieldsn = explode(",", "IP address,Subnet bit count,Gateway");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	} else if ($_POST['type'] == "PPPoE") {
		$reqdfields = explode(" ", "username password");
		$reqdfieldsn = explode(",", "PPPoE username,PPPoE password");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	} else if ($_POST['type'] == "PPTP") {
		$reqdfields = explode(" ", "pptp_username pptp_password pptp_local pptp_subnet pptp_remote");
		$reqdfieldsn = explode(",", "PPTP username,PPTP password,PPTP local IP address,PPTP subnet,PPTP remote IP address");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}
	
	$_POST['spoofmac'] = str_replace("-", ":", $_POST['spoofmac']);
	
	if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) {
		$input_errors[] = "A valid IP address must be specified.";
	}
	if (($_POST['subnet'] && !is_numeric($_POST['subnet']))) {
		$input_errors[] = "A valid subnet bit count must be specified.";
	}
	if (($_POST['gateway'] && !is_ipaddr($_POST['gateway']))) {
		$input_errors[] = "A valid gateway must be specified.";
	}
	if (($_POST['pointtopoint'] && !is_ipaddr($_POST['pointtopoint']))) {
		$input_errors[] = "A valid point-to-point IP address must be specified.";
	}
	if (($_POST['provider'] && !is_domain($_POST['provider']))) {
		$input_errors[] = "The service name contains invalid characters.";
	}
	if (($_POST['pptp_local'] && !is_ipaddr($_POST['pptp_local']))) {
		$input_errors[] = "A valid PPTP local IP address must be specified.";
	}
	if (($_POST['pptp_subnet'] && !is_numeric($_POST['pptp_subnet']))) {
		$input_errors[] = "A valid PPTP subnet bit count must be specified.";
	}
	if (($_POST['pptp_remote'] && !is_ipaddr($_POST['pptp_remote']))) {
		$input_errors[] = "A valid PPTP remote IP address must be specified.";
	}
	if (($_POST['spoofmac'] && !is_macaddr($_POST['spoofmac']))) {
		$input_errors[] = "A valid MAC address must be specified.";
	}

	if (ipv6enabled()) {
		if ($_POST['ipv6mode'] == "static" && !is_ipaddr6($_POST['ipaddr6'])) {
			$input_errors[] = "A valid IPv6 address must be specified.";
		}
		if ($_POST['ipv6mode'] == "static" && !is_ipaddr6($_POST['gateway6'])) {
			$input_errors[] = 'A valid IPv6 gateway must be specified.';
		}
	}
	
	/* Wireless interface? */
	if (isset($optcfg['wireless'])) {
		$wi_input_errors = wireless_config_post();
		if ($wi_input_errors) {
			$input_errors = array_merge($input_errors, $wi_input_errors);
		}
	}

	if (!$input_errors) {
	
		unset($wancfg['ipaddr']);
		unset($wancfg['subnet']);
		unset($wancfg['gateway']);
		unset($wancfg['pointtopoint']);
		unset($wancfg['dhcphostname']);
		unset($wancfg['ipaddr6']);
		unset($wancfg['subnet6']);
		unset($wancfg['gateway6']);
		unset($config['pppoe']['username']);
		unset($config['pppoe']['password']);
		unset($config['pppoe']['provider']);
		unset($config['pptp']['username']);
		unset($config['pptp']['password']);
		unset($config['pptp']['local']);
		unset($config['pptp']['subnet']);
		unset($config['pptp']['remote']);
	
		if ($_POST['type'] == "Static") {
			$wancfg['ipaddr'] = $_POST['ipaddr'];
			$wancfg['subnet'] = $_POST['subnet'];
			$wancfg['gateway'] = $_POST['gateway'];
			if (isset($wancfg['ispointtopoint']))
				$wancfg['pointtopoint'] = $_POST['pointtopoint'];
		} else if ($_POST['type'] == "DHCP") {
			$wancfg['ipaddr'] = "dhcp";
			$wancfg['dhcphostname'] = $_POST['dhcphostname'];
		} else if ($_POST['type'] == "PPPoE") {
			$wancfg['ipaddr'] = "pppoe";
			$config['pppoe']['username'] = $_POST['username'];
			$config['pppoe']['password'] = $_POST['password'];
			$config['pppoe']['provider'] = $_POST['provider'];
		} else if ($_POST['type'] == "PPTP") {
			$wancfg['ipaddr'] = "pptp";
			$config['pptp']['username'] = $_POST['pptp_username'];
			$config['pptp']['password'] = $_POST['pptp_password'];
			$config['pptp']['local'] = $_POST['pptp_local'];
			$config['pptp']['subnet'] = $_POST['pptp_subnet'];
			$config['pptp']['remote'] = $_POST['pptp_remote'];
		}
		
		$wancfg['blockpriv'] = $_POST['blockpriv'] ? true : false;
		$wancfg['spoofmac'] = $_POST['spoofmac'];
		
		if (ipv6enabled()) {
			if ($_POST['ipv6mode'] == "6to4") {
				$wancfg['ipaddr6'] = "6to4";
				unset($wancfg['subnet6']);
				unset($wancfg['gateway6']);
			} else if ($_POST['ipv6mode'] == "static") {
				$wancfg['ipaddr6'] = $_POST['ipaddr6'];
				$wancfg['subnet6'] = $_POST['subnet6'];
				$wancfg['gateway6'] = $_POST['gateway6'];
			} else {
				unset($wancfg['ipaddr6']);
				unset($wancfg['subnet6']);
				unset($wancfg['gateway6']);
			}
		}
			
		write_config();
		
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval = interfaces_wan_configure();
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
	}
}
?>
<?php include("fbegin.inc"); ?>
<script type="text/javascript">
<!--
function enable_change(enable_over) {
<?php if (ipv6enabled()): ?>
	var en = (document.iform.ipv6mode.selectedIndex == 1 || enable_over);
	document.iform.ipaddr6.disabled = !en;
	document.iform.subnet6.disabled = !en;
	document.iform.gateway6.disabled = !(document.iform.ipv6mode.selectedIndex == 1 || enable_over);
<?php endif; ?>
	
	if (document.iform.mode) {
		 wlan_enable_change(enable_over);
	}
}

function type_change() {
	switch (document.iform.type.selectedIndex) {
		case 0:
			document.iform.username.disabled = 1;
			document.iform.password.disabled = 1;
			document.iform.provider.disabled = 1;
			document.iform.ipaddr.disabled = 0;
			document.iform.subnet.disabled = 0;
			document.iform.gateway.disabled = 0;
			document.iform.pptp_username.disabled = 1;
			document.iform.pptp_password.disabled = 1;
			document.iform.pptp_local.disabled = 1;
			document.iform.pptp_subnet.disabled = 1;
			document.iform.pptp_remote.disabled = 1;
			document.iform.dhcphostname.disabled = 1;
			break;
		case 1:
			document.iform.username.disabled = 1;
			document.iform.password.disabled = 1;
			document.iform.provider.disabled = 1;
			document.iform.ipaddr.disabled = 1;
			document.iform.subnet.disabled = 1;
			document.iform.gateway.disabled = 1;
			document.iform.pptp_username.disabled = 1;
			document.iform.pptp_password.disabled = 1;
			document.iform.pptp_local.disabled = 1;
			document.iform.pptp_subnet.disabled = 1;
			document.iform.pptp_remote.disabled = 1;
			document.iform.dhcphostname.disabled = 0;
			break;
		case 2:
			document.iform.username.disabled = 0;
			document.iform.password.disabled = 0;
			document.iform.provider.disabled = 0;
			document.iform.ipaddr.disabled = 1;
			document.iform.subnet.disabled = 1;
			document.iform.gateway.disabled = 1;
			document.iform.pptp_username.disabled = 1;
			document.iform.pptp_password.disabled = 1;
			document.iform.pptp_local.disabled = 1;
			document.iform.pptp_subnet.disabled = 1;
			document.iform.pptp_remote.disabled = 1;
			document.iform.dhcphostname.disabled = 1;
			break;
		case 3:
			document.iform.username.disabled = 1;
			document.iform.password.disabled = 1;
			document.iform.provider.disabled = 1;
			document.iform.ipaddr.disabled = 1;
			document.iform.subnet.disabled = 1;
			document.iform.gateway.disabled = 1;
			document.iform.pptp_username.disabled = 0;
			document.iform.pptp_password.disabled = 0;
			document.iform.pptp_local.disabled = 0;
			document.iform.pptp_subnet.disabled = 0;
			document.iform.pptp_remote.disabled = 0;
			document.iform.dhcphostname.disabled = 1;
			break;
		case 4:
			document.iform.username.disabled = 1;
			document.iform.password.disabled = 1;
			document.iform.provider.disabled = 1;
			document.iform.ipaddr.disabled = 1;
			document.iform.subnet.disabled = 1;
			document.iform.gateway.disabled = 1;
			document.iform.pptp_username.disabled = 1;
			document.iform.pptp_password.disabled = 1;
			document.iform.pptp_local.disabled = 1;
			document.iform.pptp_subnet.disabled = 1;
			document.iform.pptp_remote.disabled = 1;
			document.iform.dhcphostname.disabled = 1;
			break;
	}
}
//-->
</script>
<?php if (isset($optcfg['wireless'])): ?>
<script language="javascript" src="interfaces_wlan.js"></script>
<?php endif; ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
            <form action="interfaces_wan.php" method="post" name="iform" id="iform">
              <table width="100%" border="0" cellpadding="6" cellspacing="0" summary="content pane">
                <tr> 
                  <td valign="middle"><strong>Type</strong></td>
                  <td><select name="type" class="formfld" id="type" onchange="type_change()">
                      <?php $opts = split(" ", "Static DHCP PPPoE PPTP");
				foreach ($opts as $opt): ?>
                      <option <?php if ($opt == $pconfig['type']) echo "selected";?>> 
                      <?=htmlspecialchars($opt);?>
                      </option>
                      <?php endforeach; ?>
                    </select></td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" height="4"></td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">General configuration</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">MAC address</td>
                  <td class="vtable"> <input name="spoofmac" type="text" class="formfld" id="spoofmac" size="30" value="<?=htmlspecialchars($pconfig['spoofmac']);?>"> 
                    <br>
                    This field can be used to modify (&quot;spoof&quot;) the MAC 
                    address of the WAN interface<br>
                    (may be required with some cable connections)<br>
                    Enter a MAC address in the following format: xx:xx:xx:xx:xx:xx 
                    or leave blank</td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">Static IP configuration</td>
                </tr>
                <tr> 
                  <td width="100" valign="top" class="vncellreq">IP address</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="ipaddr" type="text" class="formfld" id="ipaddr" size="20" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
                    / 
                    <select name="subnet" class="formfld" id="subnet">
                    <?php
                      if (isset($wancfg['ispointtopoint']))
                      	$snmax = 32;
                      else
                      	$snmax = 31;
                      for ($i = $snmax; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['subnet']) echo "selected"; ?>> 
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select></td>
                </tr><?php if (isset($wancfg['ispointtopoint'])): ?>
                <tr>
                  <td valign="top" class="vncellreq">Point-to-point IP address </td>
                  <td class="vtable">
                    <?=$mandfldhtml;?><input name="pointtopoint" type="text" class="formfld" id="pointtopoint" size="20" value="<?=htmlspecialchars($pconfig['pointtopoint']);?>">
                  </td>
                </tr><?php endif; ?>
                <tr> 
                  <td valign="top" class="vncellreq">Gateway</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="gateway" type="text" class="formfld" id="gateway" size="20" value="<?=htmlspecialchars($pconfig['gateway']);?>"> 
                  </td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">DHCP client configuration</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">Hostname</td>
                  <td class="vtable"> <input name="dhcphostname" type="text" class="formfld" id="dhcphostname" size="40" value="<?=htmlspecialchars($pconfig['dhcphostname']);?>">
                    <br>
                    The value in this field is sent as the DHCP client identifier 
                    and hostname when requesting a DHCP lease. Some ISPs may require 
                    this (for client identification).</td>
                </tr>
				<?php if (ipv6enabled()): ?>
                <tr> 
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">IPv6 configuration</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">IPv6 mode</td>
                  <td class="vtable"> 
                    <select name="ipv6mode" class="formfld" id="ipv6mode" onchange="enable_change(false)">
                      <?php $opts = array('disabled', 'static', '6to4');
						foreach ($opts as $opt) {
							echo "<option value=\"$opt\"";
							if ($opt == $pconfig['ipv6mode']) echo "selected";
							echo ">$opt</option>\n";
						}
						?>
                    </select><br>
					6to4 mode will automatically try to establish an IPv6-over-IPv4 tunnel via the
					nearest gateway. You also need to set your LAN interface (and optional interfaces, if present)
					to 6to4 mode for it to work properly.</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">IPv6 address</td>
                  <td class="vtable"> 
                    <input name="ipaddr6" type="text" class="formfld" id="ipaddr6" size="30" value="<?=htmlspecialchars($pconfig['ipaddr6']);?>">
                    / 
                    <select name="subnet6" class="formfld" id="subnet6">
                      <?php for ($i = 127; $i > 0; --$i): ?>
                      <option value="<?=$i;?>" <?php
                        if ($i == $pconfig['subnet6'] || (!isset($pconfig['subnet6']) && $i == 64)) echo "selected";
                      ?>>
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select></td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">IPv6 gateway</td>
                  <td class="vtable"> 
                    <input name="gateway6" type="text" class="formfld" id="gateway6" size="30" value="<?=htmlspecialchars($pconfig['gateway6']);?>">
                   </td>
                </tr>
				<?php endif; ?>
                <tr> 
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">PPPoE configuration</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">Username</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="username" type="text" class="formfld" id="username" size="20" value="<?=htmlspecialchars($pconfig['username']);?>"> 
                  </td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">Password</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="password" type="text" class="formfld" id="password" size="20" value="<?=htmlspecialchars($pconfig['password']);?>"> 
                  </td>
                </tr>
                <tr> 
                  <td valign="top" class="vncell">Service name</td>
                  <td class="vtable"><input name="provider" type="text" class="formfld" id="provider" size="20" value="<?=htmlspecialchars($pconfig['provider']);?>"> 
                    <br> <span class="vexpl">Hint: this field can usually be left 
                    empty</span></td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" height="16"></td>
                </tr>
                <tr> 
                  <td colspan="2" valign="top" class="listtopic">PPTP configuration</td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">Username</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="pptp_username" type="text" class="formfld" id="pptp_username" size="20" value="<?=htmlspecialchars($pconfig['pptp_username']);?>"> 
                  </td>
                </tr>
                <tr> 
                  <td valign="top" class="vncellreq">Password</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="pptp_password" type="text" class="formfld" id="pptp_password" size="20" value="<?=htmlspecialchars($pconfig['pptp_password']);?>"> 
                  </td>
                </tr>
                <tr> 
                  <td width="100" valign="top" class="vncellreq">Local IP address</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="pptp_local" type="text" class="formfld" id="pptp_local" size="20" value="<?=htmlspecialchars($pconfig['pptp_local']);?>">
                    / 
                    <select name="pptp_subnet" class="formfld" id="pptp_subnet">
                      <?php for ($i = 31; $i > 0; $i--): ?>
                      <option value="<?=$i;?>" <?php if ($i == $pconfig['pptp_subnet']) echo "selected"; ?>> 
                      <?=$i;?>
                      </option>
                      <?php endfor; ?>
                    </select></td>
                </tr>
                <tr> 
                  <td width="100" valign="top" class="vncellreq">Remote IP address</td>
                  <td class="vtable"><?=$mandfldhtml;?><input name="pptp_remote" type="text" class="formfld" id="pptp_remote" size="20" value="<?=htmlspecialchars($pconfig['pptp_remote']);?>"> 
                  </td>
                </tr>
                <?php /* Wireless interface? */
				if (isset($optcfg['wireless']))
					wireless_config_print();
				?>
                <tr> 
                  <td height="16" colspan="2" valign="top"></td>
                </tr>
                <tr> 
                  <td valign="middle">&nbsp;</td>
                  <td class="vtable">
                <a name="rfc1918"></a><input name="blockpriv" type="checkbox" id="blockpriv" value="yes" <?php if ($pconfig['blockpriv']) echo "checked"; ?>> 
                    <strong>Block private networks</strong><br>
					When set, this option blocks traffic from IP addresses
					that are reserved for private networks as per RFC 1918
					(10/8, 172.16/12, 192.168/16) as well as loopback addresses
					(127/8). You should generally leave this option turned on, 
					unless your WAN network lies in such a private address space,
					too.</td>
                </tr>
                <tr> 
                  <td width="100" valign="top">&nbsp;</td>
                  <td> &nbsp;<br> <input name="Submit" type="submit" class="formbtn" value="Save" onClick="enable_change(true)"> 
                  </td>
                </tr>
              </table>
</form>
<script type="text/javascript">
<!--
enable_change(false);
type_change();
<?php if (isset($optcfg['wireless'])): ?>
wlan_enable_change(false);
<?php endif; ?>
//-->
</script>
<?php include("fend.inc"); ?>
