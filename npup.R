npup_parse1<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    link_list<-c()
    
    for (i in 1:16) {
        url<-paste("http://www.planning.ntpc.gov.tw/ap/fdownload/fdownload2.jsp?mfkind_id=00038&enpage=",i,sep="")        
        get_url<-getURL(url, encoding="utf-8")
        get_url_parse<-htmlParse(get_url, encoding="utf-8")
        
        value_parse<-xpathSApply(get_url_parse,"//form[@name='fdownload']/table/tr/td/table/tbody/tr/td[@class='font-2']",xmlValue)
        file_name<-as.character(iconv(value_parse[seq(2,length(value_parse),8)],"utf-8","big5"))
        
        file_type<-value_parse[seq(4,length(value_parse),8)]    
        
        link<-xpathSApply(get_url_parse,"//form[@name='fdownload']/table/tr/td/table/tbody/tr/td/div/a[@href]",xmlAttrs)[1,]
        link<-paste("http://www.planning.ntpc.gov.tw",link,sep="")
        
        list<-data.frame(name=file_name,link=link,type=file_type,stringsAsFactors=FALSE)
        
        link_list<-rbind(link_list,list)
    }
    write.csv(link_list,"npup_link_list.csv",row.names=FALSE)
}