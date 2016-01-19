===================================================
	DASAN Enterprise MIBs readme.txt

	contact to : dhlee@dasannetworks.com
	             thkim3@dasannetworks.com
===================================================
version : 1.0 #build 5 (20061207)
TAG : DASAN-MIBS-1-0-05-20061207
==[ ChangeLog ]=======================
<2006/12/07>
 dasan-gigabit-optic-transceiver.mib
  - ifindex -> ifIndex
<2006/11/11>
 dasan-products-mib.mib>
  - Add V6416F/V6224F/V6024OP/V5924N/V5924C/V5948
 dasan-smi.mib>
  - Add dsSystemuUpgradeChanged Trap (dasanEvents 44)
 dasan-switch-mib.mib>
  - Add length 64 to dsPortType
  - Add dsHardwareAddress
  - Add dsTimeZone
  - Add dsSystemNosInfo

  - Add dsVdslPortPerfCountClear
  - Add dsVdslPortSuperFrameRxCount
  - Add dsVdslPortSuperFrameTxCount
  - Add dsVdslPortSuperFrameCountClear
  - Add dsVdslPortPerfRxCRC
  - Add dsVdslPortPerfTxCRC
  - Add dsVdslPortUpSNRMinMargin
  - Add dsVdslPortDownSNRMinMargin
  - Add dsVdslPortUpINP
  - Add dsVdslPortDownINP
  - Add dsVdslPortMiiInBytes
  - Add dsVdslPortMiiInBPS
  - Add dsVdslPortMiiOutBytes
  - Add dsVdslPortMiiOutBPS
  - Add dsVdslHardwareAddress

  - Add dsNTPServerTable
  - Add dsDNSServerTable
  - Add dsSyslogConfTable
  - Add dsNetworkTimeProtocolInfo
 dasan-gigabit-optic-transceiver.mib>
  - change DsGoTransceiverTransceiverType(add fiber-1000-cx)
 dasan-access-mib.mib>
  - Add dsControlPortTestRing
  - Add dsMgcpEndpointIdTable
  - Add dsAccGwyAutoConfigServer
  - Add dsAccGwyAutoUpgradeServer

  - dsMgcpEncodePackageName : Change defval 0 -> 1
  - dsMgcpRetransmitStartTimeout : Change  defval 200 -> 400(mesc)
  - dsMgcpRetransmitMaxTimeout : Change  defval 4 -> 4000(msec)
  - dsMgcpRetransmitMax1 : Change  defval 3
  - dsMgcpRetransmitMax2 : Change  defval 5
  - dsMgcpRestartMaxwait : Change  defval 5 
  - dsMgcpDisconnectInit : Change defval  8
  = dsMgcpDisconnectMin : Change  defval 8
  - dsMgcpDisconnectMax : Change  defval 600
  - dsMgcpCaPort1 : Change range 0-65535 defval 2427 
  - dsMgcpCaPort2 : Change range 0-65535 defval 2427 
  - dsMgcpCaPort3 : Change range 0-65535 defval 2427 
  - dsMgcpCaPort4 : Change range 0-65535 defval 2427 
  - dsMgcpCaPort5 : Change range 0-65535 defval 2427 
  - dsMgcpCaPort6 : Change range 0-65535 defval 2427 
  - dsMgcpCaPort7 : Change range 0-65535 defval 2427 
  - dsMgcpCaPort8 : Change range 0-65535 defval 2427 
  - dsMgcpMgAddr : Change range 0-65535 defval 2427 
  - dsMgcpMgPort : Change range 0-65535 defval 2427 

  - dsSlotCodecPacketizationPeriodG711 : Change  range 10, 20, 30 defval 10
  - dsSlotCodecPacketizationPeriodG723 : Change  30(30),60(60) defval 30
  - dsSlotCodecPacketizationPeriodG729 : Change  10(10),20(20),30(30)   defval 20

  - Del dsSlotJitterbufferDynamic, Add dsSlotJitterbuffer
  - Del slotJitterbufferStaticMax
  - dsSlotRingonTime : Change range 500-1000
  - dsSlotRingoffTime : Change ragne 500-2000 
  - dsSlotHookflashMin : Change range 100-600 defval 200 -> 300 (msec)
  - dsSlotHookflashMax : Change range 100-1000 defval 500 -> 800 (msec)
  - dsSlotInterdigitTimeout : Change range 1000-10000 defval 8 -> 4000 unit (msec)
  - dsSlotEce : Change syntax (INTEGER -> DisplayString), Add defval "on 15"
  - dsSlotFax : Change synaxt (on/off -> t30, 538, off)

  - dsSlotCng : Change defval 1 -> 0
  - defSlotOodbtmf : Change 1 -> 0
  - dsSlotNetworkHostName : Change syntax read-write -> read-only
  - dsSlotNetworkDhcp : Change defval 1 -> 0

  - dsPortIvol : Change range 1-7 defval 3 ->4 
  - dsPortOvol : Change range 1 - 7 defval 3 - >4
  - dsPortPortBlock : Change defval 0

<2006/06/02>
  - rename dasan-gepon.mib -> dasan-gepon-mib.mib
  - rename dasan-qos.mib -> dasan-qos-mib.mib

<2006/05/29>
  dasan-products-mib.mib
   - Add V1824E
   - Add V5224G
   - Add V5908L
  dasan-switch-mib.mib
   - Add descriptions for dsPortRateLimitUnit, IngressThreshold, EgressThreshold

<2006/05/10>
  dasan-products-mib.mib
   - hix5830 -> hix5430
   - Add V2124J/V2116J
   - Add V1816MD/V1808MD

<2006/07/26>
  dasan-products-mib.mib
   - Add v1816EL/v1808EL
   - Add v1816E/v1808E
   - Add hiD6615-S324  (V5424)

======================================


======================================
<2009/03/12>
Add DSSHE
 - ds-she-adsl-mib.mib      ds-she-mplspw-mib.mib         ds-she-shdsl-mib.mib
 - ds-she-alarming-mib.mib  ds-she-mta-mib.mib            ds-she-sync-mib.mib
 - ds-she-bridge-mib.mib    ds-she-physical-mib.mib       ds-she-system-mib.mib
 - ds-she-ethernet-mib.mib  ds-she-pm-dr-mib.mib          ds-she-tc-mib.mib
 - ds-she-gbond-mib.mib     ds-she-pots-linetest-mib.mib  ds-she-topology-mib.mib
 - ds-she-idxctrl-mib.mib   ds-she-pots-mib.mib           ds-she-tssm-mib.mib
 - ds-she-igmp-mib.mib      ds-she-products-mib.mib       ds-she-voip-mib-module.mib
 - ds-she-isdn-mib.mib      ds-she-rmon-mib.mib           ds-she-xdsl-mib.mib
 - ds-she-l2cp-mib.mib      ds-she-selt-mib.mib
Add DSMPLS
 - dpw-atm-mib.mib
 - dpw-redundancy-mib.mib
 - dsmpls-ldp-lsr-pw-mib.mib
ADD MPLS standard MIBs
 - mpls-ldp-atm-std-mib.mib
 - mpls-ldp-frame-relay-std-mib.mib
 - mpls-ldp-generic-std-mib.mib
 - mpls-ldp-std-mib.mib
 - mpls-lsr-std-mib.mib
 - mpls-tc-std-mib.mib
 - mpls-te-std-mib.mib
 - IANA-PWE3-MIB.my 
 - pw-std-mib.mib
 - pw-tc-std-mib.mib

<2009/03/12>
 ds-she-system-mib.mib
  - Add sheSystemFileControlServiceType/ sheSystemSwmServiceType/ sheSystemSwmControlServiceType 
  - Modify sheSystemFtp/ sheSystemSwmFTPError/ sheSystemSwmFtpAccessError/ sheSystemSwmFtpExternalAccessError 
