khhup_parse1<-function(){
    library(bitops)
    library(RCurl)
    library(XML)

    link.list<-data.frame(url=character(),name=character(),stringsAsFactors = FALSE)
    
    for(i in 1:29){
        get.url<-getURLContent(paste0("http://kupc.kcg.gov.tw/KUPC/web_page/KPP0013.jsp?KP005002=&KP006003=&SD=&ED=&KP005008=A&PNO=",i))
        get.url.parse<-htmlParse(get.url)
        link<-xpathApply(get.url.parse, "//table//tr//td[@class='text13']//a[@target='_blank']", xmlAttrs)
        if(class(link)=="list") link<-rapply(link,c)
        
        filelink<-link[names(link)=="href"]
        filename<-iconv(link[names(link)=="title"],"utf-8","big5")
        link.list<-rbind(link.list,cbind(filelink,filename),stringsAsFactors=FALSE)
    }
    
    link.list$filename<-gsub("\\(pdf檔\\)","",link.list$filename)
    link.list$filetype<-substr(link.list$filelink,nchar(link.list$filelink)-2,nchar(link.list$filelink))
    link.list$filelink<-gsub("(\\.\\.)","http://kupc.kcg.gov.tw/KUPC",link.list$filelink)
    
    write.csv(link.list,"./record/KHHUP/khhup_link_list.csv",row.names=FALSE)
}

dlrecord<-function(csvfile){
    library(httr)
    
    link.list<-read.csv(csvfile,stringsAsFactors=FALSE)
    
    pb <- txtProgressBar(max = nrow(link.list), style = 3)
    
    link.list<-link.list[!grepl("20110516114926",link.list$filelink),]
    
    for(i in 1:nrow(link.list)){
        link<-GET(link.list$filelink[i])
        link.name<-link.list$filename[i]
        filelocale<-paste0("./record/KHHUP/raw/",link.name,".",link.list$filetype[i])
        download.file(link$url,filelocale,mode="wb",quiet=TRUE)
        
        setTxtProgressBar(pb, i)
    }    
}