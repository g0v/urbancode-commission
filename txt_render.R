test<-function(){
   
txt<-readLines("./record/tpup/txt/tpup670.txt",encoding="UTF-8")
txt<-txt[!grepl("*-*-$",txt)]
txt2<-gsub(" ","",txt)

s.indicator<-grep("^(壹|貳|參|肆|伍|陸|柒|捌|玖|拾)",txt)
s.title<-txt[grepl("^(壹|貳|參|肆|伍|陸|柒|捌|玖|拾)",txt)]
    
s1<-txt[1:s.indicator[1]-1]
s2<-txt[s.indicator[1]:s.indicator[2]-1]
s3<-txt[s.indicator[2]:s.indicator[3]-1]
s4<-txt[s.indicator[3]:length(txt)]
    
s3.1<-s3[s3.indicator[2]:(s3.indicator[3]-1)]
s3.2<-s3[s3.indicator[3]:(s3.indicator[4]-1)]
s3.3<-s3[s3.indicator[4]:length(s3)]
    
    s3.1.parse<-c()
    j<-0 
    
for(i in 1:length(s3.1)){
    if(s3.1[i]=="^(一|二|三|四|五|六|七|八|九|十)、*"){
        j<-j+1
        s3.1.parse[j]<-s3.1[i]
    } else if(s3.1[i]=="*( )+*"){
        j<-j+1
        s3.1.parse[j]<-s3.1[i]
    } else if(s3.1[i]=="^( )+*"){
        s3.1.parse[j]<-paste(s3.1.parse[j],s3.1[i],sep="")
    }
}
}

get_url<-"./record/tpup/html/tpup672.pdf/page5.html"
html.parse<-htmlParse(get_url,encoding="utf-8")
parse.txt<-xpathSApply(html.parse,"//div[@class='txt']",xmlValue)
parse.loc<-xpathSApply(html.parse,"//div[@class='txt']",xmlAttrs)
parse.loc.left<-gsub(".*left:(.*?);.*","\\1",parse.loc[2,])
parse.loc.top<-gsub(".*top:(.*?);.*","\\1",parse.loc[2,])
parse.df<-data.frame(txt=parse.txt,loc.left=parse.loc.left,loc.top=parse.loc.top)