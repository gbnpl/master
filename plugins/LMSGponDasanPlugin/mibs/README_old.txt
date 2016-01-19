--------------------------------------------------------------
--
-- DASAN MIBs Readme.txt
-- Contact to dhl@da-san.com
--
--------------------------------------------------------------
<LISTS>
dasan-smi.mib
 --> DASAN SMI File : define basic & dasan enterprise trap.(it will be moved)
dasan-tc.mib
 --> DASAN Textual Conventions.
dasan-products-mib.mib
 --> DASAN product OIDs
dasan-switch-mib.mib
 --> DASAN switch & xDSL enterprise MIBs
dasan-dhcp.mib
 --> DASAN switch DHCP MIBs
dasan-dsl-mib.mib
 --> DASAN SDSL-specific MIBs.
dasan-router-mib.mib
 --> DASAN Router MIBs.
dasan-dhcp-r.mib
 --> DASAN Router DHCP MIBs.
enclosure-mib.mib
 --> Other Enterprise product related to xDSL V59xx
VDSL-LINE.my
 --> 2003/06 Internet Draft 10. VDSL MIBs
HC-NUM.my
 --> ZeroBasedCounter64 for VDSL-LINE.my
HC-PERFHIST-TC.my
 --> Textual Convention for VDSL-LINE.my
dasan-adsl-mib.mib
 --> DASAN ADSL MIBs.
dasan-shdsl-mib.mib
 --> DASAN SHDSL MIBs.
dasan-qos.mib
 --> DASAN QoS MIBs.
dasan-gepon.mib
 --> DASAN GEPON MIBs.
dasan-snmp.mib
 --> DASAN SNMP MIBs.
dasan-bridge.mib
 --> DASAN Bridge/vlan MIBs.
dasan-access-mib.mib
dasan-access-slot-h248-mib.mib
dasan-access-slot-mgcp-mib.mib
dasan-access-slot-pots-mib.mib
 --> AccessGateWay(VoIP) Protocol & Management MIBs.
dasan-igmp-snooping-mib.mib
 --> DASAN igmpSnooping mib.
pim-mib.mib  
 --> draft-ietf-pim-mib-v2-00.txt  June 2002
dasan-gigabit-optic-transceiver-mib.mib
 --> GBIC/SFP module information mib.
dasan-epon-mib.mib
 --> GEPON MIB
dasan-ts-1000-mib.mib
 --> TS-1000 module info
dasan-user-management.mib
 --> user management .



---------------------------------------------------------
-- VERSION INFO : current V5.7
---------------------------------------------------------
V1.0  : 2003/07/24
        - modify DASAN-SWITCH-MIB
V1.1  : 2003/07/29
        - modify DASAN-PRODUCTS-MIB
V1.2  : 2003/08/07
        - modify DASAN-PRODUCTS-MIB ( add V1500, V2500, V2600 )
V1.3  : 2003/08/10
        - modify DASAN-PRODUCTS-MIB ( add V2108, V2116, V2124 )
V1.4  : 2003/08/26
        - modify DASAN-PRODUCTS-MIB ( add V1008 )
V1.5  : 2003/10/22
        - remove VDSL-LINE-MIB & add VDSL-LINE-MIB-11.
        - remove HC-PREFHIST-TC-MIB & add HC-PREFHIST-TC-MIB-05.
V1.6  : 2003/12/19
        - Add VDSL model V5916-DMT50
        - Add VDSL profile (4-band)
V1.7  : 2004/02/14
        - Add cpe-nos upgrade 
        - Add VDSL modem reset
        - Add VDSL LIU Reboot
V1.8  : 2004/02/18
        - Add CPEINFO to xDSLportTable
        - Add CPEDOWNLOAD to xDSLportTable

V1.9  : 2004/03/08
        - Add User Mac Table

V2.0  : 2004/03/12
        - Add hardwareVersion to dSwitchSystem.
        - change VDSL-LINE-MIBs OID to 97.
        - remove variable cpuload5s in cpuload trap
V2.1  : 2004/03/23
        - Add systemRestart Trap

V2.2  : 2004/03/24
        - Change portIngressRate/portEgressRate from ASN_INTEGER to ASN_COUNTER

V2.3  : 2004/04/12
        - Add SwitchAtTable (arp information). 
        - Add Mac-flood-guard trap(19, 20)

V2.4  : 2004/04/21
        - Add xDslSlotLIUCPEDOWN

V2.5  : 2004/05/10
        - Add psdMaskLevel entries in xdslporttable/xdslslottable.

V2.6  : 2004/06/10
        - Add tcTable 
        - Add module option notification in DASAN-SMI  

V2.7  : 2004/06/15
        - Add V1716 to product list 
        - Add Memory Threshold Over/Fall Trap(25,26) in DASAN-SMI  

V2.8  : 2004/06-22
	- change TRAP-TYPE to NOTIFICATION-TYPE
	- modify variable, Entity name

V2.9  : 2004/06-25
	- add xDSLportLineConfProfileClear, xDSLportAlarmConfProfileClear OBJECT

V3.0  : 2004/07-05
	- added V1724plus DASAN-PRODUCTS-MIB
        - added dhcpIllegalEntry Trap 

V3.1  : 2004/07-08
        - added ADSL Product V5800, V5809 into product list
        - added V5524, V1324 into product list

V3.2  : 2004/08-18
        - modified portMaxHost, portNego, portDuplex in DASAN-SWITCH-MIB 
        - added portSpeed into DASAN-SWITCH-MIB

V3.3  : 2004/08-30
        - added TRAP-TYPE of enclosureDoorTrap 
        - added V5924-LR50 oid
        - modified portFlowControl in DASAN-SWITCH-MIB
        - added VDSL Error Counters Clear, LineActiveTime Clear function

V3.4  : 2004/09-15
        - made DASAN-QOS MIB file 

V3.5  : 2004/10-07
        - made ADSL2PLUS MIB files 

V3.6  : 2004/10-18
        - added FanStatus : other(3) -- none of the following
        - modified some objects : notavailable => other 
        - added slotInstalledTrap, slotRemovedTrap in DASAN-SMI
        - added adslAtucPerfLofsThresh, adslAtucPerfLossThresh .. into DASAN-SMI
        - added more slot Information. 
        - modified some objects in DASAN-ADSL-MIB

V3.7  : 2004/10-29
	- added portInstall value list
	- added softwareCompatibility

V3.8  : 2004/12-13
        - added V1624, V1616, V1608 into product list
        - added V2602A in product list
        - added PSD-K to DSLslotPSDMaskLevel value list

V3.9  : 2005/01-13
        - added G.SHDSL MIB files 
        - modified object's name according to Naming Convention 

V4.0  : 2005/02-10
        - added dsPortCurrUpTime, dsPortPrevUpTime into DASAN-SWITCH-MIB file. 
        - added vdsl EWL and vdsl CPE error count into DASAN-SWITCH-MIB file.
        - added asym100_100_998n at Vdsl Up/Downstream profile. 
        - added v5924SB(VDSL) into product list
        - added hiX5630, hiX5635_M1200 into product list
        - added v5824(ADSL), v5848(ADSL), v5810, v5817 into product list

V4.1  : 2005/03-25
        - added dasanGEPON product into product list 
        - added v5524OP, v5524EL into product list
        - added CRC objects into DASAN-ROUTER-MIB file
        - added CRC trap into DASAN-SMI file
        - added value list to dsAdslAtucPhysExtnActualStd in DASAN-ADSL-MIB file
        - added value list to dsAdslLineConfProfileExtnPsdMaskType in DASAN-ADSL-MIB file

V4.2  : 2005/05-10
        - added value lists to dsVdslPortUpProfile/dsVdslPortDownProfile in DASAN-SWITCH-MIB file.
        - added value lists to dsVdslPortPBOLength  in DASAN-SWITCH-MIB file. 
        - added v2308, v2316, v2324 into product list
        - made dasan GEPON MIB file  
        - added dasan GEPON products into product list 
        - added GEPON Notification types into SMI file. 

V4.3  : 2005/05-16
        - added SIEMENS system oids into product list
        - added value lists to dsVdslPortHamband, dsVdslPortHamband OBJECT 
        - changed DASAN-DHCP-MIB file name 

V4.4  : 2005/07-08
        - appended VoIP MIB
        - added v6108G, v5212G, v6324F, 6424 into product list
        - added sym100/100 Profiles into DASAN-SWITCH-MIB
        - assigned " 1.3.6.1.4.1.6296.9.1.1.101 " oid to NetStar MIB.  
        - added dsAdslLineConfProfileExtnPMMode, dsAdslLineConfProfileExtnPML0Time, dsAdslLineConfProfileExtnPML2ATPR,
          dsAdslLineConfProfileExtnPML2Rate, dsAdslLineConfProfileExtnPML2EntryThresholdRate etc into DASAN-ADSL-MIB 
        - added some traps(Power Management State Change, Oper State Change, ESThresh,SESThresh,
          CRCanomaliesThresh,LOSWSThresh,UASThresh) into DASAN-ADSL-MIB  and DASAN-SHDSL-MIB 
 

V4.5  : 2005/07-22
        -  Add INDEX { sleViewTreeFamilySubtree  } to sleViewTreeFamilyTable

V4.6  : 2005/07-26
        -  Add dsUserMacAddress5 ~ 8 to dsUserMacTable.
        -  Add dsSerialNumber to dsSwitchSystem

V4.7  : 2005/08-06
        -   DASAN-ACCESS-MIB
            Change dsAccGwyControlSlotTable
             -  DsAccGwyControlSlotEntryList -> DsAccGwyControlSlotEntry 
             -  INDEX  { slotIndex } -> INDEX { dsControlSlotIndex }
             -  remove dsControlSlotUpgradeFtp and dsControlSlotUpgradeTftp
     
            Change dsAccGwyControlPortTable
             -  DsAccGwyControlPortEntryList -> DsAccGwyControlPortEntry
             -  INDEX  { portIndex } -> INDEX { dsControlPortIndex }

            Change dsAccGwyConfigSlotTable
             -  DsAccGwyConfigSlotEntryList -> DsAccGwyConfigSlotEntry
             -  INDEX  { slotIndex } -> INDEX { dsSlotCodeIndex }
             -  dsSlotinstallStatus -> dsSlotInstStatus
             -  dsSlotindex -> dsSlotCodeIndex

            Change dsAccGwyConfigPortTable
             - DsAccGwyConfigPortEntryList -> dsAccGwyConfigPortEntry
             - dsPortIndex -> dsAccPortIndex

        - DASAN-ADSL-MIB
             Change the type of dsAdslLineAlarmExtnAtucPmStateTrapEnable from INTEGER to INTEGER32 
             Change the type of dsAdslLineAlarmExtnAtucOpStateTrapEnable from INTEGER to INTEGER32 

V4.8 : 2005/08/17
        - DASAN-PRODUCT-MIB.my
             Add V5216 of V5100 series
	     Add hiX5630M800, hiX5635M1200 of v5800 series
        - Newly Add DASAN-SNMP.my
        - Newly Add DASAN-BRIDGE.my

V4.9 : 2005/09/06
        - DASAN-PRODUCT-MIB.my
	     Add V4604s, V4610s, V4664 of AccessGateWay(VoIP)
        - Newly Add DASAN-ACCESS-SLOT-H248-MIB.mib
	- Newly Add DASAN-ACCESS-SLOT-MGCP-MIB.mib
	- Newly Add DASAN-ACCESS-SLOT-POTS-MIB.mib

V5.0 : 2005/09/23
	- fix DASAN-SMI.mib compile error

V5.1 : 2005/10/20
        - All file name was changed to lowercase.
        - Change dasan-access-slot-h248-mib.mib totally.
        - Newly add v1624MD 
   	- Newly add v5424G
	- Newly add v1824
   	- Change hiX5630M800 -> hiX5630M600 
   	- Change hiD6615M223 -> hiD6615M323
   	- Change oid of hiD6615M223 to 18.
   	- Newly add hiX5630V-M600V
        - Fix syntax errors of dasan-switch-mib.my, dasan-bridge.my and dasan-shdsl-mib.mib
	  - dasan-bridge-mib    : line 25,27 -> delete ','.
	  - dasan-shdsl-mib     : line 1457,1470 -> dsShdslStatus4WireHsMode change type from Unsigned32 to INTEGER.
	  - dasan-switch-mib.my : line 535,544 -> reset(1) -> reset.

V5.2 : 2005/11/1 ~ 9 
	- Newly Add v1816, v1808
	- Newly Add v5916SB
	- Newly Add v5624M400

V5.3 : 2005/11/29
	- Add file dasan-igmp-snooping-mib.mib
        - Newly Add sleV2Mgmt in dasan-smi.mib

V5.4 : 2005/12/12
	- Newly Add v1824EL
	- Add dsFirmwareVersion 
        - Add removed(0) to dsPortInstallStatus

V5.4 : 2005/12/12
	- Newly Add v1824EL
	- Add dsFirmwareVersion 
        - Add removed(0) to dsPortInstallStatus
       2005/12/13
   	- Change hix5625-M400 to hiX5625M400 of v5800 17
        - remove the followings from dasan-products.mib
	      hiX5630-M600      OBJECT IDENTIFIER ::= { hiX 11 }
	      hiX5635-M1200     OBJECT IDENTIFIER ::= { hiX 12 }
	      hiX5630V-M600V    OBJECT IDENTIFIER ::= { hiX 13 }
	      hiX5625-M400      OBJECT IDENTIFIER ::= { hiX 14 }
------------------------------------------------------------------------
V5.4 : 2005/12/17
	- Change dsSerialNumber from INTEGER to DisplayString
        - Add V5724G

V5.5 : 2006/01/02
        - Add dasan-gigabit-optic-transceiver-mib.mib
        - Add dasan-epon-mib.mib
       2006/01/06
        - Change v5916SB to v5916B
        - Add v5908B 
       2006/01/09
        - Add V4208

V5.6 : 2006/01/24
        - Add temperatureOverHighThreshold
        - Add temperatureFallHighThreshold
        - Add temperatureOverLowThreshold
        - Add temperatureFallLowThreshold
       2006/01/27
        - Add cpuLoadHighOverThreshold
        - Add cpuLoadHighFallThreshold
        - Add cpuLoadLowOverThreshold
        - Add cpuLoadLowFallThreshold

V5.7
       2006/02/05
        - Add V1624CWDM
       2006/02/24
        - Add hix5830(V5724G)
       2006/03/14
        - Add V6424EL/OP
        - Add V5924B
       2006/03/20
        - add dasan ts-100 mib(dasan-ts1000-mib.mib).
        - add dasan user mib (dasan-user-management.mib).
       2006/03/29
        - add V1824MD

V5.8 
       2006/05/10
	- add V2116J & V2124J
	- change hix5830 -> hix5430
