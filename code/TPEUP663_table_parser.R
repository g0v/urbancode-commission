test.for<-function(){
test<-readLines("./record/TPEUP/txt/tpup663.txt",encoding = "UTF-8")
ind<-grep("陳情意見綜理表$",test)
ind2<-grep("審議事項 二",test)
t.table<-test[ind[1]:(ind2-1)]
t.table[grep("第*頁$",t.table)]<-NA
t.table<-t.table[!is.na(t.table)]
line.len<-vector(mode="integer",length(t.table))
for(i in 1:length(t.table)){line.len[i]<-nchar(t.table[i])}
line.split<-strsplit(t.table,"")
line.space<-rep(list(NULL),length(line.split))
for(i in 1:length(line.split)){line.space[[i]]<-grep(" ",line.split[[i]])}
space.count<-as.data.frame(table(unlist(line.space)),stringsAsFactors = FALSE)
max.cnt<-as.integer(space.count[order(space.count[,2],decreasing=TRUE),][1,1])
##for(i in 1:length(t.table)){
##    if(t.table[[i]]==""){t.table[[i]]="rowline"}
##}
c1<-vector(mode="character",length=length(t.table))
c2<-vector(mode="character",length=length(t.table))


for(i in 1:length(t.table)){
    c1[i]<-paste(line.split[[i]][1:(max.cnt-1)],collapse="")
    if(length(line.split[[i]]) < max.cnt){
        c2[i]<-""
    } else {
        c2[i]<-paste(line.split[[i]][(max.cnt+1):length(line.split[[i]])],collapse="")
    }
}
}