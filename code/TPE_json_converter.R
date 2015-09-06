json_convert<-function(result,target){
    ##block 1:header & tailer
    json_header<-vector(mode="character",length=length(result[[1]]))
    
    for (i in 1:length(result[[1]])){json_header[i]<-header_parse(result[[1]][i])}
    
    tailer<-paste(result[[length(result)]])
    t.vector<-unique(na.omit(as.numeric(unlist(strsplit(tailer, "[^0-9]+")))))
    t.hour<-t.vector[1]
    t.minute<-t.vector[2]
    e.time<-paste(t.hour,":",t.minute,sep="")
    json_header[2]<-paste(json_header[2],",\"end_time\":\"",e.time,"\"",sep="")
    
    json_header<-paste(json_header,collapse=",")
    
    ##block 2:body
    json_body<-rep(list(NULL),(length(result)-2))
    
    for(m in 2:(length(result)-1)){
        for(i in 1:length(result[[m]])){
            json_body_txt<-paste(body_txt_parse(result[[m]][[i]][[1]]),collapse=",")
            if(length(result[[m]][[i]])==2){
                json_body_table<-paste(body_table_parse(result[[m]][[i]][[2]]),collapse=",")
                json_body[[m-1]][[i]]<-paste("{",paste(c(json_body_txt,json_body_table),collapse=","),"}",sep="")
            } else {
                json_body[[m-1]][[i]]<-paste("{",json_body_txt,"}",sep="")
            }
        }
        
        name.tag<-""
        if(grepl("報告事項",names(result[m]))){
            name.tag<-"report_item"
        } else if(grepl("審議事項",names(result[m]))){
            name.tag<-"deliberate_item"
        } else if(grepl("臨時動議",names(result[m]))){
            name.tag<-"extempore_item"
        } else {
            name.tag<-"other"
        }
    
        json_body[[m-1]]<-paste("\"",name.tag,"\":[",paste(json_body[[m-1]],collapse=","),"]",sep="")
    }
    
    json_body<-paste(json_body,collapse=",")
    
    ##block 3:export json
    json_fulltxt<-paste("{",json_header,",",json_body,"}",sep="")
    
    f.name<-paste("./record/TPEUP/JSON/",target,".json",sep="")
    write(json_fulltxt,file=f.name)
}

header_parse<-function(header_txt){
    if(Encoding(header_txt)=="UTF-8"){header_txt<-iconv(header_txt,"UTF-8","BIG5")}
    if (grepl("臺北市都市計畫委員會第(.*)次委員會議紀錄",header_txt)){
        session<-gsub(".*第 (.*) 次.*","\\1",header_txt)
        jsontxt<-paste("\"title\":\"",header_txt,"\",\"session\":",session,sep="")
        return(jsontxt)
    }
    else if (grepl("^時間",header_txt)) {
        m.year<-gsub(".*中華民國 (.*) 年.*","\\1",header_txt)
        m.month<-gsub(".*年 (.*) 月.*","\\1",header_txt)
        m.day<-gsub(".*月 (.*) 日.*","\\1",header_txt)
        m.date<-paste(m.year,"/",m.month,"/",m.day,sep="")
        
        t.hour<-gsub(".*午 (.*) 時.*","\\1",header_txt)
        t.minute<-gsub(".*時 (.*) 分.*","\\1",header_txt)
        s.time<-paste(t.hour,":",t.minute,sep="")
        
        jsontxt<-paste("\"date\":\"",m.date,"\",\"start_time\":\"",s.time,"\"",sep="")
        return(jsontxt)
    }
    else if (grepl("^地點",header_txt)){
        location<-gsub("^地點(：|:)(.*)$","\\2",header_txt)
        jsontxt<-paste("\"location\":\"",location,"\"",sep="")
        return(jsontxt)
    }
    else if (grepl("^主席",header_txt)){
        chairman<-gsub("^主席(：|:)(.*)$","\\2",header_txt)
        jsontxt<-paste("\"chairman\":\"",chairman,"\"",sep="")
        return(jsontxt)
    }
    else if (grepl("^彙整",header_txt)){
        note_taker<-gsub("^彙整(：|:)(.*)$","\\2",header_txt)
        jsontxt<-paste("\"note_taker\":\"",note_taker,"\"",sep="")
        return(jsontxt)
    }
    else if (grepl("^出席委員",header_txt)){
        attend_committee<-gsub("^出席委員(：|:)(.*)$","\\2",header_txt)
        jsontxt<-paste("\"attend_committee\":\"",attend_committee,"\"",sep="")
        return(jsontxt)
    }
    else if (grepl("^列席單位",header_txt)){
        attend_unit<-gsub("^列席單位人員(：|:)(.*)$","\\2",header_txt)
        jsontxt<-paste("\"attend_unit\":\"",attend_unit,"\"",sep="")
        return(jsontxt)
    }
}

body_txt_parse<-function(body_txt){
    json.vector<-vector(mode="character",length=length(body_txt))
    
    for(i in 1:length(body_txt)){
        if(is.null(names(body_txt[i]))){
            value<-paste("\"",body_txt[[i]],"\"",sep="",collapse=",")
            json.vector[1]<-paste("\"null\":[",value,"]",sep="")
        } else {
            if(grepl("事項",names(body_txt[i]))){
                case<-gsub("案名(：|:)(*)","\\2",body_txt[[i]][[1]])
                json.vector[1]<-paste("\"case\":\"",case,"\"",sep="")
            }
            else if(grepl("說明(：|:)",names(body_txt[i]))){
                description<-paste("\"",body_txt[[i]],"\"",sep="",collapse=",")
                json.vector[i]<-paste("\"description\":[",description,"]",sep="")
            }
            else if(grepl("^決議(：|:)",names(body_txt[i]))){
                resolution<-paste("\"",body_txt[[i]],"\"",sep="",collapse=",")
                json.vector[i]<-paste("\"resolution\":[",resolution,"]",sep="")
            }
            else if(grepl("^附帶決議(：|:)",names(body_txt[i]))){
                add_resolution<-paste("\"",body_txt[[i]],"\"",sep="",collapse=",")
                json.vector[i]<-paste("\"add_resolution\":[",add_resolution,"]",sep="")
            }
            else {
                other_content<-paste("\"",body_txt[[i]],"\"",sep="",collapse=",")
                json.vector[i]<-paste("\"other_content\":[",other_content,"]",sep="")
            }
        }
    }
    
    return(json.vector)
}

body_table_parse<-function(body_table){
    json.vector<-vector(mode="character",length=length(body_table))
    
    for(i in 1:length(json.vector)){
        table.txt<-paste("\"",gsub("\"","(quote)",unlist(body_table[[i]])),"\"",collapse=",",sep="")
        ##table.txt<-paste("\"",unlist(body_table[[i]]),"\"",collapse=",",sep="")
        json.vector[i]<-paste("[",table.txt,"]",sep="")
    }
    
    tabletxt<-paste(json.vector,collapse=",",sep="")
    jsontxt<-paste("\"petition\":[",tabletxt,"]",sep="")
    
    return(jsontxt)
}