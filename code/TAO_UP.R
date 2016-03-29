taoup_parse1<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    #Sys.setlocale(category='LC_ALL', locale='C')

    link_list<-c()
    
    for(i in 1:2){
        get_url<-getURL(paste0("http://www.ud.taichung.gov.tw/lp.asp?CtNode=22422&CtUnit=6506&BaseDSD=7&mp=127010&pagesize=30&nowPage=",i), encoding="utf-8")
        get_url_parse<-htmlParse(get_url, encoding="utf-8")
        
        list<-as.vector(xpathSApply(get_url_parse,"//div[@class='list']//ul//li//a",xmlAttrs))
        if(class(list)=="list") list<-rapply(list,c)
        link_list<-c(link_list,list)
        #print(head(list))
    }
    
    names(link_list)<-NULL
    link_list<-link_list[-grep("_blank",link_list)]
    
    file_link<-link_list[seq(1,length(link_list),2)]
    file_name<-link_list[seq(2,length(link_list),2)]
    
    df<-data.frame(weblink=file_link,stringsAsFactors = FALSE)
    df[,1]<-paste0("http://www.ud.taichung.gov.tw/",df[,1])
    write.csv(df,"./record/TAOUP/taoup_web_list.csv",row.names=FALSE)
}

taoup_parse2<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    
    weblist<-read.csv("./record/TAOUP/taoup_web_list.csv",stringsAsFactors = FALSE)
    
    #filetype<-c()
    link_list<-data.frame(url=character(),name=character(),stringsAsFactors = FALSE)
    pb <- txtProgressBar(max = nrow(weblist), style = 3)
    
    for(i in 1:nrow(weblist)){
        get_url <- getURLContent(weblist[i,1], encoding="utf-8")
        get_url_parse <- htmlParse(get_url, encoding="utf-8")
        
        link<-as.vector(xpathApply(get_url_parse, "//section[@class='cp']//a", xmlAttrs))
        if(class(link)=="list") link<-rapply(link,c)
        
        name<-as.vector(xpathApply(get_url_parse, "//section[@class='cp']//a", xmlValue))
        if(class(name)=="list") name<-rapply(name,c)
        
        link_list<-rbind(link_list,cbind(link[names(link)=="href"],name),stringsAsFactors = FALSE)
        #xpathApply(get_url_parse, "//section[@class='cp']//a", xmlValue)
        #iconv(rapply(xpathApply(get_url_parse, "//section[@class='cp']//a", xmlAttrs),c)[3],"utf-8","big5")
        setTxtProgressBar(pb, i)
    }
    colnames(link_list)<-c("filelink","filename")
    link_list$filetype<-"pdf"
    row.names(link_list)<-NULL
    
    link_list<-link_list[!grepl("#top|javascript",link_list$filelink),]
    
    link_list$filelink<-paste0("http://www.ud.taichung.gov.tw/",link_list$filelink)
    link_list$filename<-gsub("\\([0-9]+[ ]+KB\\)$","",link_list$filename)
    link_list$filename<-gsub("(上網)?.pdf| $","",link_list$filename)
    link_list$filename<-gsub("上網(版)?","",link_list$filename)
    
    write.csv(link_list,"./record/TAOUP/taoup_link_list.csv",row.names=FALSE)
}

tao_dlrecord<-function(csvfile){
    library(httr)
    
    link.list<-read.csv(csvfile,stringsAsFactors=FALSE)
    
    pb <- txtProgressBar(max = nrow(link.list), style = 3)
    
    for(i in 1:nrow(link.list)){
        link<-GET(link.list$filelink[i])
        link.name<-link.list$filename[i]
        filelocale<-paste0("./record/TAOUP/raw/",link.name,".",link.list$filetype[i])
        download.file(link$url,filelocale,mode="wb")
        
        setTxtProgressBar(pb, i)
    }    
}