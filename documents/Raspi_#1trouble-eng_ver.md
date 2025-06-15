# Cannot connect to raspi #1 from Japan after connecting it to Ethernet

## Symptoms

Before connecting Ethernet, `ssh` works without issues. However, after connecting Ethernet, even `PING 10.10.163.113.223` does not get a reply.

## Issues

### Issue 1

Below is the terminal response from raspi #2, but raspi #1 behaves the same way.

```terminal
pi@raspberrypi02:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
default via 10.163.112.1 dev eth0 proto static metric 100
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 10
10.163.112.1 dev eth0 proto static scope link metric 100
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
pi@raspberrypi02:~ $
```

The second and fourth lines are incorrect. The second line is unnecessary. I fixed this in the following part of the '/etc/NetworkManager/system-connections/WirelessConnection2.nmconnection' file by adding 'never-default=true' under 'ipv4'. This setting indicates that this node does not use router configuration.

```terminal
[connection]
id=WirelessConnection2
uuid=2302b4d7-e057-451f-acc6-9ec6cf41810c
type=wifi
interface-name=wlan1
timestamp=1748226004

[wifi]
mode=infrastructure
ssid=SMC-Data

[wifi-security]
key-mgmt=wpa-psk
psk=SMCmfg#2020#

[ipv4]
address1=10.163.113.234/20,10.163.112.1
method=manual
route-metric=10

[ipv6]
addr-gen-mode=stable-privacy
method=disabled

[proxy]
```

Previously, the '[ipv4]' setting was set as 'method=auto'.

### Issue 2

Raspi #1 had another problem. The setting in the '/etc/NetworkManager/system-connections/WirelessConnection2.nmconnection' file was as follows:

```terminal
[ipv4]
address1=192.168.1.10/24,10.163.112.1
method=manual
never-default=true
```

The second line was cut off in the middle. It was corrected to:

```terminal
pi@raspberrypi02:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
pi@raspberrypi02:~ $
```

### Issue 3 (Unresolved)

After the above two fixes, raspi #1 now responds to 'PING', but ssh still fails with the following error:

```terminal
PS C:\Users\odaseiji> ssh pi@10.163.113.223
ssh: connect to host 10.163.113.223 port 22: Connection refused
```
