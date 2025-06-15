# raspi #1 を ethenet に接続すると、日本から接続できない

## 症状

ethenet を接続する前は、`ssh`も問題ないが、ethnet を接続すると、`PING 10.10.163.113.223`すら、返事がない。

## 問題

### 問題 1

以下、ターミナルの返信は raspi#2 の物だが、raspi#1 も同じ状態。

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

これでオカシイのは 2 行目と 4 行目。2 行目は、必要ない。修正したのは、
`/etc/NetworkManager/system-connections/WirelessConnection2.nmconnection`ファイルの以下の部分。`ipv4`に`never-default=true`を加えた。このノードはルーター設定を使わないの意味。

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

この中の`[ipv4]`の設定が、`method=auto`になっていた。

### 問題 2

raspi#1 はもう一つ問題が有った。同じ`/etc/NetworkManager/system-connections/WirelessConnection2.nmconnection`ファイルの中の設定が

```
[ipv4]
address1=192.168.1.10/2
method=manual
never-default=true
```

この 2 行目が、途中で切れている。これを以下の様に修正。

```terminal
[ipv4]
address1=192.168.1.10/24,10.163.112.1
method=manual
never-default=true
```

これで、以下のようになる。

```terminal
pi@raspberrypi02:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
pi@raspberrypi02:~ $
```

### 問題 3（未解決）

上記 2 つの修正で raspi#1 の`PING`への返信あるが、`ssh`は以下の様に接続できない。

```terminal
PS C:\Users\odaseiji> ssh pi@10.163.113.223
ssh: connect to host 10.163.113.223 port 22: Connection refused
```
