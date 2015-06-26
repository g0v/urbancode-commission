tpup_parse1<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    get_url<-getURL("http://www.tupc.gov.taipei/lp.asp?CtNode=6308&CtUnit=4388&BaseDSD=7&mp=120021&nowPage=1&pagesize=200", encoding="utf-8")
    get_url_parse<-htmlParse(get_url, encoding="utf-8")
        
    link <- xpathSApply(get_url_parse,"//div[@class='list']/ul/li/a",xmlAttrs)
    link<-data.frame(weblink=link[1,],record_name=link[2,],stringsAsFactors = FALSE)
    
    for(i in 1:nrow(link)){
        link[i,1]<-paste("http://www.tupc.gov.taipei/",link[i,1],sep="")
        
        txt<-link[i,2]
        txt<-gsub(">","",gsub("<","%",txt))
        txt<-iconv(URLdecode(txt),"utf-8","big5")
        link[i,2]<-txt            
    }
    
    write.csv(link,"tp_web_list.csv",row.names=FALSE)
}

tpup_parse2<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    weblist<-read.csv("tp_web_list.csv",stringsAsFactors = FALSE)
        
    filetype<-c()
    pb <- txtProgressBar(max = nrow(weblist), style = 3)
    
    for(i in 1:nrow(weblist)){
        if(grepl("doc|pdf", weblist[i,1])==FALSE){
            get_url <- getURL(weblist[i,1], encoding="utf-8")
            get_url_parse <- htmlParse(get_url, encoding="utf-8")
            node_parse <- xpathApply(get_url_parse, "//div[@class='download']/ul/li", xmlChildren)
            txt_parse <- toString.XMLNode(node_parse)
            weblist[i,1] <- sub(".*?href=\"(.*?)\".*", "\\1", txt_parse)
            weblist[i,1] <- paste("http://www.tupc.gov.taipei/",weblist[i,1],sep="")
        } else {
            weblist[i,2] <- sub("(.*?),.*","\\1",weblist[i,2])
        }
        filetype <- c(filetype, checkfiletype(weblist[i,1]))  
        
        setTxtProgressBar(pb, i)
    }
    weblist <- cbind(weblist,filetype)
    
    write.csv(weblist,"tpup_link_list.csv",row.names=FALSE)
}

checkfiletype <- function(txt){
    if(grepl(".*pdf", txt)==TRUE) {
        return("pdf")
    } else if(grepl(".*doc", txt)==TRUE) {
        return("doc")
    }
}

tp_dlrecord<-function(csvfile){
    library(httr)
    
    link.list<-read.csv(csvfile,stringsAsFactors=FALSE)
    
    pb <- txtProgressBar(max = 840, style = 3)
    
    for(i in 1:nrow(link.list)){
        link<-GET(link.list[i,1])
        filelocale<-paste("./record/tpup/raw/",link.list[i,2],".",link.list[i,3],sep="")
        download.file(link$url,filelocale,mode="wb")
        
        setTxtProgressBar(pb, i)
    }    
}
