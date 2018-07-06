<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <script src="https://ru.vuejs.org/js/vue.js"></script>
    <script src="https://unpkg.com/vue-resource@1.5.1"></script>
</head>
<body>
    <div id="vue">
    <form @submit.prevent="getSinglePost()">
        <textarea name="query" v-model="ui"></textarea>
        <br/>
        <button>send</button>
    </form>
   
<br/>

<div ref="output">
    @{{message}}
</div>
</div>
<script>
        new Vue({
            el: '#vue',
            data: {
                endpoint: 'http://pageparser.loc',
                message: '',
                ui:'',
            },
            methods: {
                getSinglePost: function() {
                    this.$http.post(this.endpoint,{"query":this.ui}).then(function(response) {
                        this.$refs.output.innerHTML = response.data
                    })
                },
            },
        
        });
</script>
</body>
</html>