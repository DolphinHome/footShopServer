Vue.component('dialog-temp',{
    template:'#template',
    props:{
        dialogFormVisible:{
            type:Boolean,
            default:false
        }
    },
    watch: {
        dialogFormVisible(newVal,oldVal){
            console.log(newVal,oldVal)
        }
    },
})
new Vue({
    el:'#app',
    data(){
        return{
            dialogFormVisible:true 
        }
    },
    mounted() {
        
    },
    methods: {
        dialogFormVisible1(){
            console.log(this.dialogFormVisible)
            this.dialogFormVisible = true;
        }
    },
})
