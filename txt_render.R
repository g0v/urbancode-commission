txt<-readLines("./record/tpup/txt/tpup670.txt",encoding="UTF-8")
txt<-txt[!grepl("*-*-$",txt)]
txt2<-gsub(" ","",txt)

s.indicator<-grep("^(壹|貳|參|肆|伍|陸|柒|捌|玖|拾)",txt)
s.title<-txt[grepl("^(壹|貳|參|肆|伍|陸|柒|捌|玖|拾)",txt)]

s1<-txt[1:s.indicator[1]-1]
s2<-txt[s.indicator[1]:s.indicator[2]-1]
s3<-txt[s.indicator[2]:s.indicator[3]-1]
s4<-txt[s.indicator[3]:length(txt)]