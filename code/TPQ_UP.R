npup_parse1<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    link_list<-c()
    
    for (i in 1:16) {
        url<-paste0("http://www.planning.ntpc.gov.tw/download/?type_id=10479&parent_id=10160&page=",i)
        #url<-paste("http://www.planning.ntpc.gov.tw/ap/fdownload/fdownload2.jsp?mfkind_id=00038&enpage=",i,sep="")
        get_url<-getURL(url, encoding="utf-8")
        get_url_parse<-htmlParse(get_url, encoding="utf-8")
        
        #value_parse<-xpathSApply(get_url_parse,"//form[@name='fdownload']/table/tr/td/table/tbody/tr/td[@class='font-2']",xmlValue)
        value_parse<-xpathSApply(get_url_parse,"//table[@id='download_box']//tr//td",xmlValue)
        file_name<-as.character(iconv(value_parse[seq(6,length(value_parse),6)],"utf-8","big5"))
        
        #for(j in 1:length(file_name)){
            #file_name[j]<-gsub(" \t","",file_name[j])
        #}
        
        link<-xpathSApply(get_url_parse,"//table[@id='download_box']//tr//td//a[@href]",xmlAttrs)[3,]
        #link<-xpathSApply(get_url_parse,"//form[@name='fdownload']/table/tr/td/table/tbody/tr/td/div/a[@href]",xmlAttrs)[1,]
        link<-paste0("www.planning.ntpc.gov.tw/download/",link)
        
        file_type<-substr(link,nchar(link)-2,nchar(link))
        #file_type<-value_parse[seq(4,length(value_parse),8)]
        
        list<-data.frame(link=link,name=file_name,type=file_type,stringsAsFactors=FALSE)
        
        link_list<-rbind(link_list,list)
    }
    write.csv(link_list,"./record/TPQUP/npup_link_list.csv",row.names=FALSE)
}

np_dlrecord<-function(csvfile){
    library(httr)
    
    link.list<-read.csv(csvfile,stringsAsFactors=FALSE)
    
    if (!dir.exists("./record/npup/raw/")) {
        dir.create("./record/npup/")
        dir.create("./record/npup/raw/")
    }
    
    pb <- txtProgressBar(max = nrow(link.list), style = 3)
    
    for(i in 1:nrow(link.list)){
        link<-GET(link.list[i,1])
        filelocale<-paste("./record/npup/raw/",link.list[i,2],".",link.list[i,3],sep="")
        download.file(link$url,filelocale,mode="wb")
        
        setTxtProgressBar(pb, i)
    }    
}