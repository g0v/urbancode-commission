tnup_parse1<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    get_url<-getURL("http://bud.tainan.gov.tw/doc/CityBoard_new2.aspx", encoding="utf-8")
    get_url_parse<-htmlParse(get_url, encoding="utf-8")
    
    item <- xpathSApply(get_url_parse,"//table[@class='p1']/tr/td/a",xmlValue)
    url <- xpathSApply(get_url_parse,"//table[@class='p1']/tr/td/a[@href]",xmlAttrs)
    link <- data.frame(url[1,],item,stringsAsFactors = FALSE)
    link[,2]<-iconv(link[,2],"utf-8","big5")
    
    filetype <- c()
    
    for(i in 1:nrow(link)){
        filetype <- c(filetype, checkfiletype(link[i,1]))
    }
    
    link <- cbind(link,filetype)
    
    write.csv(link,"./record/tnup/tnup_link_list.csv",row.names=FALSE)
}

checkfiletype <- function(txt){
    if(grepl(".*pdf", txt)==TRUE) {
        return("pdf")
    } else if(grepl(".*doc", txt)==TRUE) {
        return("doc")
    }
}

tn_dlrecord<-function(csvfile){
    library(httr)
    
    link.list<-read.csv(csvfile,stringsAsFactors=FALSE)
    
    ##if (!dir.exists("./record/tnup/raw/")) {
    ##    dir.create("./record/tnup/")
    ##    dir.create("./record/tnup/raw/")
    ##}
    
    pb <- txtProgressBar(max = nrow(link.list), style = 3)
    
    for(i in 1:nrow(link.list)){
        link<-GET(link.list[i,1])
        filelocale<-paste("./record/tnup/raw/",link.list[i,2],".",link.list[i,3],sep="")
        download.file(link$url,filelocale,mode="wb")
        
        setTxtProgressBar(pb, i)
    }    
}