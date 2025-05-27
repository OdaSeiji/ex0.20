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
