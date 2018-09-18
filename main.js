// web 框架
const Koa = require('koa');
const app = new Koa();

const fs = require('fs');

// 路由
const Router = require('koa-router');
const router = Router();

const koaBody = require('koa-body');
app.use(koaBody({
    multipart: true,
    formidable: {
        maxFileSize: 200*1024*1024
    }
}));
// 解析post请求，将参数设置到 ctx.request.body 上
const Parser = require('koa-bodyparser');
app.use(Parser());


// 数据库引用
const mysql = require('mysql2');
const connection = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '336699',
    database: 'cAuth'
});

// 加密算法
const md5 = require('md5');
let salt = 'salon'; // 加盐

// session
const session = require('koa-session');
app.keys = ['this is the secret session key of salon project']; // session 信息加密密钥
app.use(session({
    key: 'koa:sess', /** cookie的名称，可以不管 */
    maxAge: 7200000, /** (number) maxAge in ms (default is 1 days)，cookie的过期时间，这里表示2个小时 */
    overwrite: true, /** (boolean) can overwrite or not (default true) */
    httpOnly: true, /** (boolean) httpOnly or not (default true) */
    signed: true, /** (boolean) signed or not (default true) */
},app));

// const multiparty = require("multiparty");
// let form = new multiparty.Form({uploadDir:'./images/' });

/**
 * 设置访问路由
 */
// 处理文件上传
router.post('/upload', async(ctx, next) => {
    await next();
    console.log(`handle upload ...`);
    let msg = {};

    console.log(ctx.request.files.image.path);
    console.log(ctx.request.files.image.name);

    let file = ctx.request.files.image;
    const reader = fs.createReadStream(file.path);	// 创建可读流
    const ext = file.name.split('.').pop();		// 获取上传文件扩展名
    let filename = Date.now() + '.' + ext;
    const upStream = fs.createWriteStream(`images/${filename}`);		// 创建可写流
    reader.pipe(upStream);	// 可读流通过管道写入可写流

    let type = ctx.request.body.type;
    await new Promise(function(resolve, reject) {
        connection.query("insert into images values(0, ?, ?, ?)", [ctx.request.body.item_id, ctx.request.body.type, `images/${filename}`], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.insertId > 0 ) {
            user_id = value.insertId;
            msg['success'] = true;
        } else {
            msg['success'] = false;
            msg['err'] = '上传失败(插入数据失败)';
        }
    });

    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);

});
// 登陆处理
router.post('/login', async (ctx, next) => {
    await next();
    console.log(`handle login ...`);
    let msg = {};
    let flag = false;

    console.log(ctx.request.body);
    console.log(ctx.request.data);
    console.log(ctx.request.username);
    console.log(ctx.request);
    console.log(ctx.body);
    console.log(ctx.data);
    // console.log(ctx.req);

    await new Promise(function(resolve, reject) {
        connection.query("select * from user_account where user_name = '?'", [ctx.request.body.username], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
    	console.log(value);
        if ( value.length !== 0 ) {
            flag = true;
            console.log(` ---- username true`);
        } else {
            msg['success'] = false;
            msg['err'] = '没有这个用户';
        }
    });
    if ( flag ) {
        await new Promise(function(resolve, reject) {
            connection.query("select * from user_account where user_name = '?' and password = '?'",
                [ctx.request.body.username, md5(ctx.request.body.password+salt)],
                function(err, results, fields) {
                    if (err) throw err;
                    resolve(results);
                });
        }).then((value)=>{
            if ( value.length !== 0 ) {
                msg['success'] = true;
                ctx.session.user = value[0]['user_name'];
                console.log(` ---- password true`);
            } else {
                msg['success'] = false;
                msg['err'] = '密码错误';
            }
        });
    }
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 注册处理
router.post('/register', async (ctx, next) => {
    await next();
    console.log(`handle register ...`);
    let msg = {};
    let flag = false;
    let user_id;
    let user_name = ctx.request.body.user_name.trim(),
        password = ctx.request.body.password.trim(),
        real_name = ctx.request.body.real_name.trim(),
        school_id = ctx.request.body.school_id,
        profession_id = ctx.request.body.profession_id,
        phone = ctx.request.body.phone.trim(),
        email = ctx.request.body.email.trim(),
        characteristic = ctx.request.body.characteristic;
    if ( !user_name || !password || !real_name || !school_id || !profession_id || !phone || !email || !characteristic || !characteristic.length ) {
        msg['success'] = false;
        msg['err'] = '请填写所有字段';
        ctx.type = 'json';
        ctx.body = JSON.stringify(msg);
        return;
    }
    await new Promise(function(resolve, reject) {
        connection.query("select * from user_account where user_name = ?", [user_name], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.length === 0 ) {
            flag = true;
        } else {
            msg['success'] = false;
            msg['err'] = '该用户名已经被注册';
        }
    });
    if ( flag ) {
        await new Promise(function(resolve, reject) {
            connection.execute("insert into user_account values(0, ?, ?, ?, ?, ?, ?,  ?, ?)",
                [user_name, real_name, md5(password+salt), school_id, 0, profession_id, phone, email],
                function(err, results, fields) {
                    if (err) throw err;
                    // results = JSON.stringify(results);
                    resolve(results);
                });
        }).then((value)=>{
            if ( value.insertId > 0 ) {
                user_id = value.insertId;
                msg['success'] = true;
            } else {
                msg['success'] = false;
                msg['err'] = '注册失败(插入数据失败)';
            }
        });
        if ( msg['success'] ) {
            let sql = '(0, '+ user_id +', '+ characteristic[0] +')';
            for ( let k=0; k<characteristic.length-1; k++ ) {
                sql += ',(0, '+ user_id +', '+ characteristic[k+1] +')';
            }
            await new Promise(function(resolve, reject) {
                connection.execute("insert into user_characteristic values" + sql,
                    function(err, results, fields) {
                        if (err) throw err;
                        resolve(results);
                    });
            }).then((value)=>{
                console.log(value); // 插入多条特藏数据的
                if ( value.insertId > 0 ) {
                    msg['success'] = true;
                } else {
                    msg['success'] = false;
                    msg['err'] = '注册失败(插入数据失败)';
                }
            });
        }
    }
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 判断用户名是否已经被注册
router.post('/useful_name', async (ctx, next) => {
    await next();
    console.log(`check user name is useful ...`);
    let msg = {};
    console.log(ctx.request.body)
    await new Promise(function(resolve, reject) {
        connection.query("select * from user_account where user_name = '?'", [ctx.request.body.user_name], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.length === 0 ) {
            msg['success'] = true;
        } else {
            msg['success'] = false;
            msg['err'] = '该用户名已经被注册';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取学校数据
router.get('/data/school', async (ctx, next) => {
    await next();
    console.log(`get school data ...`);
    let msg = {};
    // if ( !ctx.session.user ) {
    //     msg['success'] = false;
    //     msg['err'] = '请先登陆';
    //     return;
    // }
    await new Promise(function(resolve, reject) {
        connection.query("select * from school", function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        msg['success'] = true;
        msg['data'] = value;
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取专业数据
router.get('/data/profession', async (ctx, next) => {
    await next();
    console.log(`get profession data ...`);
    let msg = {};
    // if ( !ctx.session.user ) {
    //     msg['success'] = false;
    //     msg['err'] = '请先登陆';
    //     return;
    // }
    await new Promise(function(resolve, reject) {
        connection.query("select * from profession", function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        msg['success'] = true;
        msg['data'] = value;
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取特长类别数据
router.get('/data/characteristic_type', async (ctx, next) => {
    await next();
    console.log(`get characteristic_type data ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    await new Promise(function(resolve, reject) {
        connection.query("select * from characteristic_type", function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        msg['success'] = true;
        msg['data'] = value;
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取某类别下所有特长数据
// router.post('/data/characteristic', async (ctx, next) => {
//     await next();
//     console.log(`get characteristic data ...`);
//     let msg = {};
//     if ( !ctx.session.user ) {
//         msg['success'] = false;
//         msg['err'] = '请先登陆';
//         return;
//     }
//     let type_id = ctx.request.body.type_id.trim();
//     await new Promise(function(resolve, reject) {
//         connection.query("select * from characteristic where type_id = ?", [type_id], function(err, results, fields) {
//             if (err) throw err;
//             resolve(results);
//         });
//     }).then((value) => {
//         msg['success'] = true;
//         msg['data'] = value;
//     });
//     ctx.type = 'json';
//     ctx.body = JSON.stringify(msg);
// });
//获取所有特长标签
router.get('/data/characteristic', async (ctx, next) => {
    await next();
    console.log(`get characteristic data ...`);
    let msg = {};
    // if ( !ctx.session.user ) {
    //     msg['success'] = false;
    //     msg['err'] = '请先登陆';
    //     return;
    // }
    // let type_id = ctx.request.body.type_id.trim();
    await new Promise(function(resolve, reject) {
        connection.query("select * from characteristic", function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        msg['success'] = true;
        msg['data'] = value;
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取所有项目信息数据
router.get('/projects', async (ctx, next) => {
    await next();
    console.log(`get projects data ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    await new Promise(function(resolve, reject) {
        connection.query("select project_name, date, image_url, group_number, group_number_now, progress.progress as progress_name, user_account.real_name as user from (projects left join progress on projects.progress_id = progress.id) left join user_account on user_account.id = projects.user_id order by date desc, group_number_now asc", function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        msg['success'] = true;
        msg['data'] = value;
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 根据条件(已完成,组队成功,组队未成功)获取项目信息数据
router.post('/data/projects', async (ctx, next) => {
    await next();
    console.log(`get characteriatic_type data ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    let type = ctx.request.body.type.trim();
    let sql;
    if ( type === 1 ) { // 查询已完成项目
        sql = 'where progress_id = 4';
    } else if ( type === 2 ) { // 查询组队完成项目
        sql = 'where group_number_now = group_number';
    } else if ( type === 3 ) {
        sql = 'where group_number_now < group_number';
    }
    await new Promise(function(resolve, reject) {
        connection.query("select * from projects " + sql, function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        msg['success'] = true;
        msg['data'] = value;
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 添加项目
router.post('/projects', async (ctx, next) => {
    await next();
    console.log(`create projects ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    let user_id = ctx.session.id,
        project_name = ctx.request.body.project_name.trim(),
        project_describe = ctx.request.body.project_describe.trim(),
        date = ctx.request.body.date.trim(),
        // image_url = ctx.request.body.image_url.trim(),
        group_number = ctx.request.body.group_number.trim(),
        group_number_now = ctx.request.body.group_number_now.trim(),
        group_describe = ctx.request.body.group_describe.trim(),
        process_id = ctx.request.body.process_id.trim();
    await new Promise(function(resolve, reject) {
        connection.query("insert into projects values(0, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)",
            [user_id, project_name, project_describe, date, image_url, group_number, group_number_now, group_describe, process_id], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.affectedRows > 0 ) {
            msg['success'] = true;
            msg['item_id'] = value.insertId;
        } else {
            msg['success'] = false;
            msg['err'] = '添加项目失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取项目进度定义
router.get('/data/progress', async (ctx, next) => {
    await next();
    console.log(`get progress data ...`);
    let msg = {};
    console.log(ctx.session)
    console.log(ctx.session.user);
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    console.log('after login ...');
    await new Promise(function(resolve, reject) {
        connection.query("select * from progress", function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.length > 0 ) {
            msg['success'] = true;
            msg['data'] = value;
        } else {
            msg['success'] = false;
            msg['err'] = '获取项目定义失败';
        }

    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 点赞操作(项目或新闻)
router.post('/thumb', async (ctx, next) => {
    await next();
    console.log(`thumb ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    let id = ctx.request.body.id.trim(),
        table = ctx.request.body.table.trim();
    await new Promise(function(resolve, reject) {
        connection.query("update ? set thumb_number = thumb_number + 1 where id = ?", [table, id], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.affectedRows > 0 ) {
            msg['success'] = true;
        } else {
            msg['success'] = false;
            msg['err'] = '点赞失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 取消点赞操作(项目或新闻)
router.post('/unthumb', async (ctx, next) => {
    await next();
    console.log(`unthumb ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    let id = ctx.request.body.id.trim(),
        table = ctx.request.body.table.trim();
    await new Promise(function(resolve, reject) {
        connection.query("update ? set thumb_number = thumb_number + 1 where id = ?", [table, id], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.affectedRows > 0 ) {
            msg['success'] = true;
        } else {
            msg['success'] = false;
            msg['err'] = '取消点赞失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 修改阅读量(项目或新闻)
router.post('/read', async (ctx, next) => {
    await next();
    console.log(`read ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    let id = ctx.request.body.id.trim(),
        table = ctx.request.body.table.trim();
    await new Promise(function(resolve, reject) {
        connection.query("update ? set page_views = page_views + 1 where id = ?", [table, id], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.affectedRows > 0 ) {
            msg['success'] = true;
        } else {
            msg['success'] = false;
            msg['err'] = '增加阅读量失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取新闻列表
router.get('/news', async (ctx, next) => {
    await next();
    console.log(`get all news ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    await new Promise(function(resolve, reject) {
        connection.query("select id, title, image_url, date, type, page_views, thumb_number from news", function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.length > 0 ) {
            msg['success'] = true;
            msg['data'] = value;
        } else {
            msg['success'] = false;
            msg['err'] = '获取新闻列表失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取某个新闻具体内容信息
router.post('/news', async (ctx, next) => {
    await next();
    console.log(`get detail message from one news ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    let id = ctx.request.body.id.trim();
    await new Promise(function(resolve, reject) {
        connection.query("select * from news where id = ?", [id], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.length > 0 ) {
            msg['success'] = true;
            msg['data'] = value;
        } else {
            msg['success'] = false;
            msg['err'] = '获取新闻详情失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 发布新闻
router.post('/news/creation', async (ctx, next) => {
    await next();
    console.log(`create news ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    let title = ctx.request.body.title.trim(),
        content = ctx.request.body.content.trim(),
        image_url = ctx.request.body.image_url.trim(),
        date = ctx.request.body.date.trim(),
        type = ctx.request.body.type.trim();
    await new Promise(function(resolve, reject) {
        connection.query("insert into news values(0, ?, ?, ?, ?, ?, 0, 0)",
            [title, content, image_url, date, type], function(err, results, fields) {
            if (err) throw err;
            resolve(results);
        });
    }).then((value) => {
        if ( value.affectedRows > 0 ) {
            msg['success'] = true;
        } else {
            msg['success'] = false;
            msg['err'] = '创建新闻失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 列出项目成员
router.post('/group', async (ctx, next) => {
    await next();
    console.log(`list group of projects ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    let project_id = ctx.request.body.project_id.trim();
    await new Promise(function(resolve, reject) {
        connection.query("select group.id, user_account.user_name, user_account.real_name, role.role from (`group` left join user_account on group.user_id = user_account.id) left join role on group.role_id = role.id where group.project_id = ?",
            [project_id], function(err, results, fields) {
                if (err) throw err;
                resolve(results);
            });
    }).then((value) => {
        if ( value.length > 0 ) {
            msg['success'] = true;
            msg['data'] = value;
        } else {
            msg['success'] = false;
            msg['err'] = '获取项目成员失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});
// 获取所有角色信息
router.get('/data/role', async (ctx, next) => {
    await next();
    console.log(`list roles ...`);
    let msg = {};
    if ( !ctx.session.user ) {
        msg['success'] = false;
        msg['err'] = '请先登陆';
        return;
    }
    await new Promise(function(resolve, reject) {
        connection.query("select * from role", function(err, results, fields) {
                if (err) throw err;
                resolve(results);
            });
    }).then((value) => {
        if ( value.affectedRows > 0 ) {
            msg['success'] = true;
        } else {
            msg['success'] = false;
            msg['err'] = '获取角色信息失败';
        }
    });
    ctx.type = 'json';
    ctx.body = JSON.stringify(msg);
});

// 加载router中所有定义的routes
app.use(router.routes());

module.exports = app;
