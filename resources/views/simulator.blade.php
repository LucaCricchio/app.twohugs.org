<html>
<head>
    <script src="https://code.jquery.com/jquery-1.12.1.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"
            integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS"
            crossorigin="anonymous"></script>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-6">
            <form class="form-inline">
                <div class="form-group">
                    <label class="sr-only" for="exampleInputEmail3">Email address</label>
                    <input type="email" name="email" class="form-control" id="exampleInputEmail3" placeholder="Email"
                           value="francesco.pepe91@gmail.com">
                </div>
                <div class="form-group">
                    <label class="sr-only" for="exampleInputPassword3">Password</label>
                    <input type="text" name="password" class="form-control" id="exampleInputPassword3"
                           placeholder="Password" value="ciao">
                </div>
                <button type="button" class="btn btn-primary"
                        onclick="request('/auth/login', jQuery(this).closest('form').serializeObject());">
                    Login
                </button>
            </form>


            <div class="users">
                @for ($i = 1; $i <= 5; $i++)
                    <div class="user_{{$i}}" style="padding: 10px; background-color: #f4f4f4; margin-bottom: 10px">
                        <h3>User {{ $i }}</h3>
                        <form class="form-inline">
                            <input type="hidden" name="user" value="{{$i}}">
                            <div class="form-group">
                                <textarea class="token" name="token" placeholder="token"
                                          style="width: 450px; height: 60px"></textarea>
                            </div>
                            <br>
                            <div class="form-group">
                                <label class="sr-only" for="id_{{$i}}">ID</label>
                                <input type="number" name="id" class="form-control id" id="id{{$i}}" placeholder="ID"
                                       value="" min="1">
                            </div>
                            <div class="form-group">
                                <label class="sr-only" for="geoLocalization_lat_{{$i}}">Latitudine</label>
                                <input type="text" name="geoLocalization[latitude]" class="form-control"
                                       id="geoLocalization_lat_{{$i}}"
                                       placeholder="Latitudine" value="12.4523232">
                            </div>
                            <div class="form-group">
                                <label class="sr-only" for="geoLocalization_lng_{{$i}}">Longitudine</label>
                                <input type="text" name="geoLocalization[longitude]" class="form-control"
                                       id="geoLocalization_lng_{{$i}}"
                                       placeholder="Longitudine" value="4.232234">
                            </div>
                            <hr>
                            <h3>Search</h3>
                            <button type="button" class="btn btn-primary"
                                    onclick="request('/search/begin', jQuery(this).closest('form').serializeObject());">
                                Begin
                            </button>
                            <button type="button" class="btn btn-danger"
                                    onclick="request('/search/userResponse/reject', jQuery(this).closest('form').serializeObject());">
                                Response - Reject
                            </button>
                            <button type="button" class="btn btn-success"
                                    onclick="request('/search/userResponse/accept', jQuery(this).closest('form').serializeObject());">
                                Response - Accept
                            </button>
                            <button type="button" class="btn btn-warning"
                                    onclick="request('/search/userResponse/noResponse', jQuery(this).closest('form').serializeObject());">
                                Response - No response
                            </button>

                            <hr>
                            <h3>Hugs </h3>
                            <div class="form-group">
                                <label class="sr-only" for="search_id_{{$i}}">Search id</label>
                                <input type="text" name="search_id" class="form-control"
                                       id="search_id_{{$i}}"
                                       placeholder="Search Id" value="">
                            </div>
                            <button type="button" class="btn btn-success"
                                    onclick="request('/hugs/create', jQuery(this).closest('form').serializeObject());">
                                Create
                            </button>
                            <button type="button" class="btn btn-info"
                                    onclick="request('/hugs/' + jQuery(this).closest('form').find('.id').val() + '/join', jQuery(this).closest('form').serializeObject());">
                                Join
                            </button>
                            <button type="button" class="btn btn-warning"
                                    onclick="request('/hugs/' + jQuery(this).closest('form').find('.id').val() + '/refresh', jQuery(this).closest('form').serializeObject());">
                                Refresh
                            </button>
                        </form>
                    </div>
                @endfor
            </div>

        </div>
        <div class="col-sm-6" id="response" style="max-height: 1000px; overflow-y: scroll">
        </div>
    </div>


    <div class="row" id="advanced-test">
        <div class="col-sm-6">
            <div class="commands">
                <h3>
                    Utente che lancia la ricerca
                </h3>
                <form class="form-inline" id="advanced-test-form">
                    <div class="form-group">
                        <label class="sr-only" for="adv-tester-email">Email address</label>
                        <input type="email" name="email" class="form-control" id="adv-tester-email" placeholder="Email"
                               value="francesco.pepe91@gmail.com">
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="adv-tester-password">Password</label>
                        <input type="text" name="password" class="form-control" id="adv-tester-password"
                               placeholder="Password" value="ciao">
                    </div>
                    <button type="button" class="btn btn-primary" onclick="var a = new AdvancedTest(); a.start();">
                        START
                    </button>
                </form>
            </div>
        </div>
        <div class="col-sm-6">
            <pre class="log"></pre>
        </div>
    </div>
</div>

</body>
</html>


<script>
    var i = 0;
    function request(url, data) {
        var request = {'url': url, 'data': data};

        if (typeof data['token'] != 'undefined') {
            var token = data['token'];
        } else {
            token = null;
        }
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            context: document.body,

            beforeSend: function (request) {
                if (token !== null) {
                    request.setRequestHeader("Authorization", 'Bearer ' + token);
                }
            }
        }).done(function (data) {
            $('#response').prepend("<div class='response-log'><a class='btn btn-warning delete pull-right'>X</a><pre>Request #" + (++i) + ": " + JSON.stringify(request, null, "\t") + "\n\n__________\nResponse:\n" + JSON.stringify(data, null, "\t") + "</pre></div>");
        }).fail(function (data) {
            $('#response').prepend("<div class='response-log'><a class='btn btn-warning delete pull-right'>X</a><pre>Request #" + (++i) + ": " + JSON.stringify(request, null, "\t") + "\n\n__________\nResponse: Failed, Status " + data.status + "</pre></div>");
        });
    }

    $.fn.serializeObject = function () {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    $('#response').on('click', '.response-log a.delete', function (ev) {
        $(this).parent().fadeOut(200, function () {
            $(this).remove();
        });

        return false;
    });


    var AdvancedTest = function () {
        this.lastFetchedUser = {'next': 1, 'max': 5};
        this.nextKeepAlive = 0;
        this.searchId = 0;
        this.tokens = {};
        this.$advancedTest = $('#advanced-test');
        this.$logger = this.$advancedTest.find('.log');

        this.addLog = function (logMessage) {
            this.$logger.prepend(logMessage + "\n");
        };

        this._authenticateSearchLauncher = function () {
            var $advancedTestForm = $('#advanced-test-form');
            var $this = this;
            $.ajax({
                url: '/auth/login',
                method: 'POST',
                data: $advancedTestForm.serialize(),
                context: document.body
            }).done(function (response) {
                if (response.success === true) {
                    $this.addLog("Login del launcher effettuato\n\tToken: " + response.data.token);
                    $this.tokens.launcher = response.data.token;


                    $this._launchSearch();
                } else {
                    throw "Impossibile autenticare il launcher";
                }
            }).fail(function (response) {
                $this.addLog("Request: " + JSON.stringify(request, null, "\t") + "\n\n__________\nResponse: Failed, Status " + response.status + "");
                throw "Impossibile autenticare il launcher";
            });
        };

        this._launchSearch = function () {
            var $this = this;
            $.ajax({
                url: '/search/begin',
                data: {'geoLocalization': {'latitude': 12.35325, 'longitude': 4.121212}},
                method: 'POST',
                context: document.body,

                beforeSend: function (request) {
                    request.setRequestHeader("Authorization", 'Bearer ' + $this.tokens.launcher);
                }
            }).done(function (response) {
                if (response.success === true) {
                    $this.addLog("Ricerca del launcher avviata\n\tsearch_id: " + response.data.search.id + "\n\tinProgress: " + response.data.inProgress);

                    $this.searchId = response.data.search.id;

                    if (response.data.inProgress) {
                        $this.addLog("E' in corso. Sollecito col prossimo keep alive. Fra " + response.data.nextKeepAlive + " secondi");

                        // Carico l'utente
                        var extra = JSON.parse(response.data.search.extra);
                        $this._loadFetchedUser(extra.last_fetch.user_id);

                        $this.nextKeepAlive = response.data.nextKeepAlive;
                        $this._decrementKeepAlive();
                    } else {
                        $this.addLog("E' già terminata senza successo");
                    }
                } else {
                    throw "Impossibile avviare la ricerca da parte del launcher";
                }
            }).fail(function (response) {
                $this.addLog("Request: " + JSON.stringify(request, null, "\t") + "\n\n__________\nResponse: Failed, Status " + response.status + "");
                throw "Impossibile avviare la ricerca da parte del launcher";
            });
        };

        this._decrementKeepAlive = function () {
            var $this = this;

            if (this.nextKeepAlive <= 0) {
                $this.addLog('Keep alive scaduto, sollecito il server');
                setTimeout(function () {
                    $this._proceedSearch();
                }, 1000); // Ritardo cmq il sollecito di 1 secondo (da problemi altrimenti).
            } else {
                var timer;
                if (this.nextKeepAlive <= 3) {
                    timer = this.nextKeepAlive * 1000;
                    this.nextKeepAlive = 0;
                } else {
                    this.nextKeepAlive -= 3;
                    timer = 3000;
                }
                $this.addLog('Riprovo fra ' + this.nextKeepAlive + " secondi..");
                setTimeout(function () {
                    $this._decrementKeepAlive()
                }, timer);
            }


        };

        this._proceedSearch = function () {
            var $this = this;
            $.ajax({
                url: '/search/proceed',
                data: {'id': $this.searchId},
                method: 'POST',
                context: document.body,

                beforeSend: function (request) {
                    request.setRequestHeader("Authorization", 'Bearer ' + $this.tokens.launcher);
                }
            }).done(function (response) {
                if (response.success === true) {
                    $this.addLog("Sollecito inviato\n\tinProgress: " + response.data.inProgress);

                    if (response.data.inProgress) {
                        $this.addLog("E' in corso. Sollecito col prossimo keep alive. Fra " + response.data.nextKeepAlive + " secondi");

                        // Carico l'utente
                        var extra = JSON.parse(response.data.search.extra);
                        $this._loadFetchedUser(extra.last_fetch.user_id);

                        $this.nextKeepAlive = response.data.nextKeepAlive;
                        $this._decrementKeepAlive();
                    } else {
                        $this.addLog("E' terminata");
                        if (response.data.search.success) {
                            $this.addLog("Ha trovato un utente, non devo fare nulla. Il server provvederà a notificarmi. L'app deve rimanere in attesa per un'altro intervallo di tempo.");
                        } else {
                            $this.addLog("Non ha trovato nessuno");
                        }
                        $this.addLog("__________\nResponse:\n" + JSON.stringify(data, null, "\t"));
                    }
                } else {
                    throw "Impossibile continuare la ricerca da parte del launcher";
                }
            }).fail(function (response) {
                $this.addLog("Request: " + JSON.stringify(request, null, "\t") + "\n\n__________\nResponse: Failed, Status " + response.status + "");
                throw "Impossibile continuare la ricerca da parte del launcher";
            });
        };

        this._loadFetchedUser = function (userId) {
            var $this = this;
            $this.addLog("Loading fetched user\n\tUser Id: " + userId);
            $.ajax({
                url: '/user/' + userId,
                context: document.body

            }).done(function (response) {
                var email = response.email, password = 'ciao';

                $.ajax({
                    url: '/auth/login',
                    method: 'POST',
                    data: {'email': email, 'password': password},
                    context: document.body
                }).done(function (response) {
                    if (response.success === true) {
                        $this.addLog("Fetch dell'utente " + userId + "effettuato\n\tToken: " + response.data.token);
                        var token = response.data.token;

                        $('.users .user_' + $this.lastFetchedUser.next).find('.token').html(token);
                        $('.users .user_' + $this.lastFetchedUser.next).find('.id').val($this.searchId);
                        $this.addLog("Fetch dell'utente " + userId + " effettuato e caricato nel box utente #" + $this.lastFetchedUser.next + "\n\tToken: " + response.data.token);

                        if ($this.lastFetchedUser.next >= $this.lastFetchedUser.max) {
                            $this.lastFetchedUser.next = 1;
                        } else {
                            $this.lastFetchedUser.next++;
                        }

                    } else {
                        throw "Impossibile effettuare il fetch dell'utente " + userId;
                    }
                }).fail(function (response) {
                    $this.addLog("Request: " + JSON.stringify(request, null, "\t") + "\n\n__________\nResponse: Failed, Status " + response.status + "");
                    throw "Impossibile effettuare il fetch dell'utente " + userId;
                });
            }).fail(function (response) {
                $this.addLog("Request: " + JSON.stringify(request, null, "\t") + "\n\n__________\nResponse: Failed, Status " + response.status + "");
                throw "Impossibile effettuare il fetch dell'utente " + userId;
            });
        };

        this.start = function () {
            this._authenticateSearchLauncher();
        };

    };


</script>

