html_parse<-function(){
    library(bitops)
    library(RCurl)
    library(XML)
    library(plyr)
    
    ##target<-672
    target<-671
    dir.name<-paste("./record/tpup/html/tpup",target,".pdf/",sep="")
    
    flist.df<-data.frame(fname=gsub("^(.*?)\\..*","\\1",dir(dir.name)),ftype=gsub(".*\\.(.*?)$","\\1",dir(dir.name)),stringsAsFactors = FALSE)
    flist.df$docuID<-as.integer(substr(flist.df[,1],5,length(flist.df[,1])))
    flist.df<-subset(flist.df,flist.df[,2]=="html")
    flist.df<-arrange(flist.df,flist.df[,3])
    flist.df<-flist.df[complete.cases(flist.df),]
    
    for(i in 1:nrow(flist.df)){
        get_url<-paste("./record/tpup/html/tpup",target,".pdf/",flist.df[i,1],".",flist.df[i,2],sep="")
        html.parse<-htmlParse(get_url,encoding="utf-8")
        parse.txt<-xpathSApply(html.parse,"//div[@class='txt']",xmlValue)
        parse.loc<-xpathSApply(html.parse,"//div[@class='txt']",xmlAttrs)
        parse.loc.left<-as.integer(gsub(".*left:(.*?)px;.*","\\1",parse.loc[2,]))
        parse.loc.top<-as.integer(gsub(".*top:(.*?)px;.*","\\1",parse.loc[2,]))
        parse.df<-data.frame(txt=parse.txt,loc.left=parse.loc.left,loc.top=parse.loc.top,stringsAsFactors=FALSE)
        
        if(!exists("parse.done")){parse.done<-parse.df}else{parse.done<-rbind(parse.done,parse.df)}
    }
    
    zh_number_cap<<-c("壹","貳","參","肆","伍","陸","柒","捌","玖","拾")
    zh_number<<-c("一","二","三","四","五","六","七","八","九","十")
    
    item.ind<-c(1,grep(paste("^",zh_number_cap,collapse="|",sep=""),parse.done[,1]),nrow(parse.done)+1)
    item.cnt<-length(item.ind)-1
    
    item.list<-rep(list(NULL),item.cnt)
    
    for(i in 1:item.cnt){
        item.list[[i]]<-parse.done[item.ind[i]:(item.ind[i+1]-1),]
    }
    
    item.list<-lapply(item.list,item_parse)
    
    return(item.list)
}

item_parse<-function(item.df){
    case.ind<-c(grep("^報告事項|^審議事項",item.df[,1]),nrow(item.df)+1)
    case.cnt<-length(case.ind)-1
    
    case.list<-rep(list(NULL),case.cnt)
    
    if(case.cnt==0){
        case.list[[1]]<-paragraph_parse(item.df)
    } else {
        for(i in 1:case.cnt){
            case.list[[i]]<-item.df[case.ind[i]:(case.ind[i+1]-1),]
        }
        case.list<-lapply(case.list,case_parse)
    }    
    
    return(case.list)
}

case_parse<-function(case.df){
    table.ind<-grep("意見綜理表$",case.df[,1])
    
    if(length(table.ind)!=0){
        content.list<-list(case.df[1:(table.ind-1),],case.df[table.ind:nrow(case.df),])
    } else {
        content.list<-list(case.df)
    }
    
    section.ind<-c(1,grep("說明：$|^決議|決議：$",content.list[[1]][,1]),nrow(content.list[[1]])+1)
    section.cnt<-length(section.ind)-1
    
    section.list<-rep(list(NULL),section.cnt)
    names(section.list)<-content.list[[1]][section.ind[1:(length(section.ind))-1],1]
    
    for(i in 1:section.cnt){
        section.list[[i]]<-content.list[[1]][(section.ind[i]+1):(section.ind[i+1]-1),]
    }
    
    content.list[[1]]<-section.list
    
    content.list[[1]]<-lapply(content.list[[1]],paragraph_parse)
    
    return(content.list)
}

paragraph_parse<-function(section.df){
    para.ind<-c(grep(paste("^(十)?",zh_number,"、",collapse="|",sep=""),section.df[,1]),nrow(section.df)+1)
    para.cnt<-length(para.ind)-1
        
    para.list<-rep(list(NULL),para.cnt)
   
    if(para.cnt==0){
        para.list[[1]]<-paste(section.df[,1],collapse="",sep="")
    } else {
        for(i in 1:para.cnt){
            para.list[[i]]<-paste(section.df[para.ind[i]:(para.ind[i+1]-1),1],collapse="",sep="")
        }
    }
    
    return(para.list)
}