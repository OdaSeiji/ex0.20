# ネットワーク

25/5/26、Ethernet ケーブルを接続すると、Wifi 経由で ping 返信がなくなる。でルーティングを調べる。

```terminal
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 600
```

これ 3 行目が全然要らない設定。これは、削除したい。

```terminal
sudo ip route del 10.163.112.0/20 dev wlan1 proto kernel src 10.163.113.164 metric 600
```

確認すると、以下になる

```terminal
pi@raspberrypi02:~ $ sudo ip route del 10.163.112.0/20 dev wlan1 proto kernel src 10.163.113.164 metric 600
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 600
```

確認の為、`reboot`してみる。で、確認すると、再び現れている。

```terminal
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 600
```

なので、以下のコマンドを実施。

```terminal
sudo ip addr del 10.163.113.164/20 dev wlan1
```

その結果、

```terminal
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 600
```

消えない。どうも DHCP で自動的に割り振られるらしい。ちょっと、放っておく。

```terminal
pi@raspberrypi02:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 600
pi@raspberrypi02:~ $ sudo ip route add 192.168.1.0/24 dev eth0
pi@raspberrypi02:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 600
192.168.1.0/24 dev eth0 scope link linkdown
pi@raspberrypi02:~ $
```

この状態で、`eth0`を接続してみる。もう一つ、ルーティングの優先度を上げてみた。

```terminal
pi@raspberrypi02:~ $ nmcli connection show WirelessConnection2 | grep ipv4.route-metric
ipv4.route-metric:                      -1
pi@raspberrypi02:~ $ nmcli connection modify WirelessConnection2 ipv4.route-metric 10
Error: Failed to modify connection 'WirelessConnection2': Insufficient privileges
pi@raspberrypi02:~ $ sudo nmcli connection modify WirelessConnection2 ipv4.route-metric 10
pi@raspberrypi02:~ $ nmcli connection show WirelessConnection2 | grep ipv4.route-metric
ipv4.route-metric:                      10
```

あれ？上手くいったかな、、、

```terminal
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 10
default via 10.163.112.1 dev eth0 proto static metric 100
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 10
10.163.112.1 dev eth0 proto static scope link metric 100
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
```

メトリックの値は良い感じ。
でも、eth0 へのルーティングがオカシイ。一度消して、見たが、反映されていないか？

```terminal
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 10
default via 10.163.112.1 dev eth0 proto static metric 100
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 10
10.163.112.1 dev eth0 proto static scope link metric 100
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
pi@raspberrypi02:~ $ sudo nmcli connection modify eth0 -ipv4.routes "10.163.112.1/32 0.0.0.0"
Error: unknown connection 'eth0'.
pi@raspberrypi02:~ $ ls /etc/NetworkManager/system-connections/
preconfigured.nmconnection  WiredConnection1.nmconnection  WirelessConnection2.nmconnection
pi@raspberrypi02:~ $ sudo nmcli connection modify WiredConnection1 -ipv4.routes "10.163.112.1/32 0.0.0.0"
pi@raspberrypi02:~ $ sudo nmcli connection down WiredConnection1
Connection 'WiredConnection1' successfully deactivated (D-Bus active path: /org/freedesktop/NetworkManager/ActiveConnection/2)
pi@raspberrypi02:~ $ sudo nmcli connection up WiredConnection1
Connection successfully activated (D-Bus active path: /org/freedesktop/NetworkManager/ActiveConnection/4)
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 10
default via 10.163.112.1 dev eth0 proto static metric 100
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 10
10.163.112.1 dev eth0 proto static scope link metric 100
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
pi@raspberrypi02:~ $
```

でも、やってみると、繋がっている感じある。

```terminal
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 10
default via 10.163.112.1 dev eth0 proto static metric 100
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 10
10.163.112.1 dev eth0 proto static scope link metric 100
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
pi@raspberrypi02:~ $ ping 192.168.1.2
PING 192.168.1.2 (192.168.1.2) 56(84) bytes of data.
64 bytes from 192.168.1.2: icmp_seq=1 ttl=64 time=9.18 ms
64 bytes from 192.168.1.2: icmp_seq=2 ttl=64 time=2.80 ms
^C
--- 192.168.1.2 ping statistics ---
2 packets transmitted, 2 received, 0% packet loss, time 1002ms
rtt min/avg/max/mdev = 2.798/5.988/9.178/3.190 ms
pi@raspberrypi02:~ $ ping 10.163.50.17
PING 10.163.50.17 (10.163.50.17) 56(84) bytes of data.
64 bytes from 10.163.50.17: icmp_seq=1 ttl=127 time=3.78 ms
64 bytes from 10.163.50.17: icmp_seq=2 ttl=127 time=5.38 ms
64 bytes from 10.163.50.17: icmp_seq=3 ttl=127 time=4.11 ms
^C
--- 10.163.50.17 ping statistics ---
3 packets transmitted, 3 received, 0% packet loss, time 2003ms
rtt min/avg/max/mdev = 3.776/4.421/5.375/0.688 ms
pi@raspberrypi02:~ $
```

なんか、切れるな、、、

```terminal
Database: connection Normal
47.236
Database: Data was inserted
Database: connection closed successfully


client_loop: send disconnect: Connection reset
PS C:\Users\odaseiji>
PS C:\Users\odaseiji>
PS C:\Users\odaseiji> ssh pi@10.163.113.234
pi@10.163.113.234's password:
```

最終的に、現状のルーティングは以下のようになっている。

```terminal
pi@raspberrypi02:~ $ ip route show
default via 10.163.112.1 dev wlan1 proto static metric 10
default via 10.163.112.1 dev eth0 proto static metric 100
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.164 metric 10
10.163.112.1 dev eth0 proto static scope link metric 100
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
```

これで、接続は問題が無い。

プログラムの自動起動。

# #1 ラズパイ

今日から接続。ssh でのログインは出来るが、、、`eth0`に対して、ネットワークの設定が終わっていない。

```terminal
pi@raspberrypi01:~/work $ ifconfig
eth0: flags=4099<UP,BROADCAST,MULTICAST>  mtu 1500
        ether d8:3a:dd:14:03:84  txqueuelen 1000  (Ethernet)
        RX packets 0  bytes 0 (0.0 B)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 0  bytes 0 (0.0 B)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0

lo: flags=73<UP,LOOPBACK,RUNNING>  mtu 65536
        inet 127.0.0.1  netmask 255.0.0.0
        inet6 ::1  prefixlen 128  scopeid 0x10<host>
        loop  txqueuelen 1000  (Local Loopback)
        RX packets 104  bytes 9114 (8.9 KiB)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 104  bytes 9114 (8.9 KiB)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0

wlan0: flags=4099<UP,BROADCAST,MULTICAST>  mtu 1500
        ether d8:3a:dd:14:03:87  txqueuelen 1000  (Ethernet)
        RX packets 0  bytes 0 (0.0 B)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 0  bytes 0 (0.0 B)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0

wlan1: flags=4163<UP,BROADCAST,RUNNING,MULTICAST>  mtu 1500
        inet 10.163.113.223  netmask 255.255.240.0  broadcast 10.163.127.255
        ether b0:c7:45:78:46:01  txqueuelen 1000  (Ethernet)
        RX packets 376863  bytes 59280661 (56.5 MiB)
        RX errors 0  dropped 23129  overruns 0  frame 0
        TX packets 3281  bytes 521643 (509.4 KiB)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0
```

その前に、ルーティングの優先順位を上げておくか。以下が、変更前。

```terminal
pi@raspberrypi01:~/work $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.223 metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.122.56 metric 600
```

これを以下のコマンドで、実行、変化なし？

```terminal
pi@raspberrypi01:~/work $ sudo nmcli connection modify WirelessConnection2 ipv4.route-metric 10
pi@raspberrypi01:~/work $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.223 metric 600
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.122.56 metric 600
pi@raspberrypi01:~/work $
```

以下のコマンドで有効化する必要がある。

```terminal
pi@raspberrypi01:~/work $ sudo nmcli connection up WirelessConnection2
Connection successfully activated (D-Bus active path: /org/freedesktop/NetworkManager/ActiveConnection/3)
pi@raspberrypi01:~/work $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.223 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.122.56 metric 10
```

設定完了。次、
eth0 の ip を 192.168.1.10 に固定

```terminal
sudo nmcli connection modify WiredConnection1 ipv4.method manual ipv4.addresses 192.168.1.10/2
```

その後ネットワーク設定を反映させる

```terminal
pi@raspberrypi01:~/work $ sudo nmcli connection down WiredConnection1
Error: 'WiredConnection1' is not an active connection.
Error: no active connection provided.
pi@raspberrypi01:~/work $ sudo nmcli connection up WiredConnection1
Connection successfully activated (D-Bus active path: /org/freedesktop/NetworkManager/ActiveConnection/4)
pi@raspberrypi01:~/work $ ifconfig
eth0: flags=4099<UP,BROADCAST,MULTICAST>  mtu 1500
        inet 192.168.1.10  netmask 255.255.255.0  broadcast 192.168.1.255
        ether d8:3a:dd:14:03:84  txqueuelen 1000  (Ethernet)
        RX packets 0  bytes 0 (0.0 B)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 0  bytes 0 (0.0 B)
        TX errors 0  dropped 15 overruns 0  carrier 0  collisions 0

lo: flags=73<UP,LOOPBACK,RUNNING>  mtu 65536
        inet 127.0.0.1  netmask 255.0.0.0
        inet6 ::1  prefixlen 128  scopeid 0x10<host>
        loop  txqueuelen 1000  (Local Loopback)
        RX packets 104  bytes 9114 (8.9 KiB)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 104  bytes 9114 (8.9 KiB)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0

wlan0: flags=4099<UP,BROADCAST,MULTICAST>  mtu 1500
        ether d8:3a:dd:14:03:87  txqueuelen 1000  (Ethernet)
        RX packets 0  bytes 0 (0.0 B)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 0  bytes 0 (0.0 B)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0

wlan1: flags=4163<UP,BROADCAST,RUNNING,MULTICAST>  mtu 1500
        inet 10.163.113.223  netmask 255.255.240.0  broadcast 10.163.127.255
        ether b0:c7:45:78:46:01  txqueuelen 1000  (Ethernet)
        RX packets 464180  bytes 76550757 (73.0 MiB)
        RX errors 0  dropped 30443  overruns 0  frame 0
        TX packets 4043  bytes 620402 (605.8 KiB)
        TX errors 0  dropped 3 overruns 0  carrier 0  collisions 0

pi@raspberrypi01:~/work $
```

再起動してみる。

```terminal
pi@raspberrypi01:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.223 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.122.56 metric 10
```

ルーティングは問題無。

```terminal
pi@raspberrypi01:~ $ ifconfig
eth0: flags=4099<UP,BROADCAST,MULTICAST>  mtu 1500
        ether d8:3a:dd:14:03:84  txqueuelen 1000  (Ethernet)
        RX packets 0  bytes 0 (0.0 B)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 0  bytes 0 (0.0 B)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0

lo: flags=73<UP,LOOPBACK,RUNNING>  mtu 65536
        inet 127.0.0.1  netmask 255.0.0.0
        inet6 ::1  prefixlen 128  scopeid 0x10<host>
        loop  txqueuelen 1000  (Local Loopback)
        RX packets 98  bytes 8676 (8.4 KiB)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 98  bytes 8676 (8.4 KiB)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0

wlan0: flags=4099<UP,BROADCAST,MULTICAST>  mtu 1500
        ether d8:3a:dd:14:03:87  txqueuelen 1000  (Ethernet)
        RX packets 0  bytes 0 (0.0 B)
        RX errors 0  dropped 0  overruns 0  frame 0
        TX packets 0  bytes 0 (0.0 B)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0

wlan1: flags=4163<UP,BROADCAST,RUNNING,MULTICAST>  mtu 1500
        inet 10.163.113.223  netmask 255.255.240.0  broadcast 10.163.127.255
        ether b0:c7:45:78:46:01  txqueuelen 1000  (Ethernet)
        RX packets 6741  bytes 1772799 (1.6 MiB)
        RX errors 0  dropped 433  overruns 0  frame 0
        TX packets 460  bytes 117918 (115.1 KiB)
        TX errors 0  dropped 0 overruns 0  carrier 0  collisions 0
```

eth0 の設定が無くなっている。。。
ヤバい、ルーティングがおかしい。
eth0 を接続すると、ネットワーク接続できなくなる。

```terminal
pi@raspberrypi01:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.223 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.122.56 metric 10
pi@raspberrypi01:~ $ sudo ip route add 192.168.1.0/24 dev eth0
pi@raspberrypi01:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.223 metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.122.56 metric 10
192.168.1.0/24 dev eth0 scope link linkdown
pi@raspberrypi01:~ $
```

を実行。一旦再起動。

# raspi #1 を ethernet ケーブルを繋ぐと日本から接続できない

まずは、問題のない`#2`を解析。

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

2 行目の eth0 に設定されているルーター設定が必要ない。
以下、変更後

```terminal
pi@raspberrypi02:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
default via 10.163.112.1 dev eth0 proto static metric 100
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
10.163.112.1 dev eth0 proto static scope link metric 100
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
pi@raspberrypi02:~ $
```

減った。変更したのは、

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

この中の[ipv4]の`method=manual`が変更前は`method=auto`になっていた。これを#1 にも展開。
昼頃やると、#1 に接続できない問題。#2 経由だと入れる。再起動したら、入れる。一体何だったんだ。。。

気になっている点

```terminal
pi@raspberrypi02:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
default via 10.163.112.1 dev eth0 proto static metric 100
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
10.163.112.1 dev eth0 proto static scope link metric 100
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
```

と`eth0`にルーティングの設定が入っている点。優先順位が低いので、使われることは無いはずだが、気持ち悪い。これは、eth0 の設定ファイル内の、`never-default=true`を入れてやればいい。

```terminal
[connection]
id=WiredConnection1
uuid=75b62f1f-a547-3003-a368-f95c8301303b
type=ethernet
autoconnect-priority=-999
interface-name=eth0
timestamp=1748232006

[ethernet]

[ipv4]
address1=192.168.1.10/24,10.163.112.1
method=manual
never-default=true

[ipv6]
addr-gen-mode=stable-privacy
method=disabled

[proxy]

```

そうすると、以下の様になる

```terminal
pi@raspberrypi02:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.234 metric 10
192.168.1.0/24 dev eth0 proto kernel scope link src 192.168.1.10 metric 100
pi@raspberrypi02:~ $
```

そう、こうあるべきですね。#1 も同様の処置。

```terminal
pi@raspberrypi01:~ $ ip route
default via 10.163.112.1 dev wlan1 proto static metric 10
10.163.112.0/20 dev wlan1 proto kernel scope link src 10.163.113.223 metric 10
pi@raspberrypi01:~ $
```

あれ、、、#1 のこの設定駄目じゃないか、、、？

```terminal
[connection]
id=WiredConnection1
uuid=02af3c0b-cca2-3a1f-bc6e-fe10fc9e2ed2
type=ethernet
autoconnect-priority=-999
interface-name=eth0
timestamp=1749010713

[ethernet]

[ipv4]
address1=192.168.1.10/2
method=manual
never-default=true

[ipv6]
addr-gen-mode=stable-privacy
method=disabled

[proxy]
```

これを

```terminal
[ipv4]
address1=192.168.1.10/24,10.163.112.1
method=manual
never-default=true
```

とちゃんと記述した。
そしたら、Ethernet を繋げると

```terminal
PS C:\Users\odaseiji> ssh pi@10.163.113.223
ssh: connect to host 10.163.113.223 port 22: Connection refused
```

が出るようになった。これは、何が原因か、、、
今のところ、時々ログインできるので、ログインした時に、ログを見たいが、ログが取られていない。
ログを取るソフトが動いていないことが原因。

```terminal
sudo apt update
sudo apt install rsyslog
```

が必要のようだが、これ、どうやってインストールするか。。。
Edimax EW-7811Un が欲しいな。

別途、WiFi ドングル
https://www.bureau-mikami.jp/raspberry-pi%E3%81%AE%E9%80%9A%E4%BF%A1%E7%92%B0%E5%A2%83%E6%94%B9%E5%96%84-%E5%A4%96%E4%BB%98%E3%81%91usb-wifi%E3%83%89%E3%83%B3%E3%82%B0%E3%83%AB%E3%81%AE%E6%B4%BB%E7%94%A8/?utm_source=chatgpt.com
の情報も参考。
