# 都市計畫委員會會議記錄資料庫

## 緣由
每個法定都市/區域計畫都需要經過多次的委會員審議，審議就會有審議紀錄，例如內政部區域計畫委員會紀錄，包含大大小小案件的審議過程、爭議點、人民陳情意見。但現在的會議紀錄公開方式只是放上pdf或doc文件，以會議編號歸檔，無法直接知道每次會議的案件有哪些，也無法檢索，如果能建立開放資料庫，就可以利用關鍵字搜尋，找出爭議案件在委員會被處裡的方式為何

## 專案目的
把各大委員會的會議紀錄材砍回家，作為開放資料，容易查閱檢索，串聯每一次會議過程與最終實施計畫間的關係

## 目前成果
http://commission.urbancode.tw

## 提案協作紀錄
* 都委會紀錄資料庫化 https://g0v.hackpad.tw/--BpW2Xt7s8AH
* 前端repo https://github.com/g0v/urbancode-commission3w
* (上層-開放都市計畫 http://hackfoldr.urbancode.tw/)

## License
MIT http://g0v.mit-license.org

註：都委會會議紀錄原始文件由各級委員會網站下載，版權依相關法令規範

## Code 存放說明

pipeline:
crawler -> TXT-JSON convert -> file2db

### /code
code 存放位置

#### crawler 各縣市都委會網站爬蟲
需另外引入pdfparser
需另外設置connect_mysql.php以連接資料庫(PDO方式連接)
* /code/crawler_file.php
* /code/crawler_page.php
* /code/crawler_toolbox.php
* /code/crawler_transform.php
* /code/simple_html_dom.php

#### TXT-JSON 格式轉換
txt to json parser ver.1
* /code/txt2json_convert.php
* /code/txt2json_functions.php
* /code/txt2json_class_definition.php
* /code/txt2json_variables/\*\_variables.php
* /code/txt2json_petitionParser/\*\_petitionParser.php

#### Database 置入 file2db
* code/file2db.php
* code/file2db_object_definition.php

### google drive
委員會會議記錄txt存放位置： https://drive.google.com/drive/folders/0B1Y7b2xwFvAxcXpMSXNIWEg3MWs?usp=sharing

XXX = 地區碼 沿用 ISO3166-2:TW https://zh.wikipedia.org/wiki/ISO_3166-2:TW
