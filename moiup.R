moiup<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    Sys.setlocale(category='LC_ALL', locale='C')
    
    link.list<-data.frame()

    pb <- txtProgressBar(max = 840, style = 3)
    
    i <- 0
    
    while(i<=840){
        url<-paste("http://www.cpami.gov.tw/chinese/index.php?option=com_filedownload&view=filedownload&Itemid=68&filter_cat=5&filter_gp=5&limitstart=",i,sep="")
                
        get_url<-getURL(url,encoding = "UTF-8")
        get_url_parse<-htmlParse(get_url,encoding = "UTF-8")
        
        link <- xpathSApply(get_url_parse, "//div[@class='dr_99']/a", xmlAttrs)
        link[2,]<-iconv(link[2,],"utf-8","big5")
        link[1,]<-paste("http://www.cpami.gov.tw",link[1,],sep="")
        link<-data.frame(weblink=link[1,],filename=link[2,],stringsAsFactors = FALSE)
        
        link.list<-rbind(link.list,link)
        
        setTxtProgressBar(pb, i)
        
        i<-i+15        
    }
    
    close(pb)
    
    write.csv(link.list,"moiup_link_list.csv",row.names=FALSE)
}

moi_dlrecord<-function(){
    library(httr)
    
    link.list<-read.csv("link_list.csv",stringsAsFactors=FALSE)
    
    pb <- txtProgressBar(max = 840, style = 3)
    
    for(i in 1:nrow(link.list)){
        link<-GET(link.list[i,1])
        filelocale<-paste("./record/moiup/raw/",link.list[i,2],sep="")
        download.file(link$url,filelocale,mode="wb")
        
        setTxtProgressBar(pb, i)
    }    
}

