package com.jpcg.viptest;
import android.app.*; import android.os.*; import android.content.*; import android.view.*; import android.widget.*;
import org.json.JSONObject; import javax.net.ssl.HttpsURLConnection; import java.io.*; import java.net.*; import java.nio.charset.StandardCharsets; import java.text.SimpleDateFormat; import java.util.*;

public class MainActivity extends Activity {
 static final String BASE="https://jpcg.onrender.com", PREFS="jpcg_prefs";
 EditText login,code; TextView output,vipExpiry,vipRemaining; Button redeem,status; LinearLayout redeemPanel,vipCard; SharedPreferences prefs;
 public void onCreate(Bundle b){super.onCreate(b);setContentView(R.layout.activity_main);
  login=findViewById(R.id.login);code=findViewById(R.id.code);output=findViewById(R.id.output);redeem=findViewById(R.id.redeem);status=findViewById(R.id.status);
  redeemPanel=findViewById(R.id.redeemPanel);vipCard=findViewById(R.id.vipCard);vipExpiry=findViewById(R.id.vipExpiry);vipRemaining=findViewById(R.id.vipRemaining);
  prefs=getSharedPreferences(PREFS,MODE_PRIVATE);String saved=prefs.getString("login_id","");login.setText(saved);
  redeem.setOnClickListener(v->redeemNow());status.setOnClickListener(v->checkNow());
  if(!saved.isEmpty()){busy("Checking membership…");new Thread(()->runStatus(saved)).start();}
 }
 void redeemNow(){String l=login.getText().toString().trim(),c=code.getText().toString().trim().toUpperCase(Locale.US);
  if(l.isEmpty()||c.isEmpty()){show("Enter both the login identifier and JPANL code.");return;}
  if(!c.matches("^JPANL[0-9]{9}$")){show("Invalid JPANL code format.");return;}
  prefs.edit().putString("login_id",l).apply();busy("Activating membership…");new Thread(()->runRedeem(l,c)).start();
 }
 void checkNow(){String l=login.getText().toString().trim();if(l.isEmpty()){show("Enter the login identifier first.");return;}
  prefs.edit().putString("login_id",l).apply();busy("Refreshing membership…");new Thread(()->runStatus(l)).start();
 }
 void runRedeem(String l,String c){try{JSONObject a=new JSONObject();a.put("login",l);a.put("code",c);JSONObject s1=post("/redeemvip.php",a);
  if(s1.optBoolean("error",true))throw new Exception(s1.optString("message","Redemption rejected"));
  JSONObject v=new JSONObject();v.put("session",s1.optString("session"));v.put("data",s1.optString("data"));JSONObject s2=post("/verify.php",v);
  if(s2.optBoolean("error",true))throw new Exception(s2.optString("message","Verification rejected"));runStatus(l);
 }catch(Exception e){show("ACTIVATION FAILED\n\n"+e.getMessage());}}
 void runStatus(String l){try{JSONObject j=get("/account_status.php?login="+URLEncoder.encode(l,"UTF-8"));String st=j.optString("state");long x=j.optLong("vip_expiry");
  if("active".equalsIgnoreCase(st)&&x>System.currentTimeMillis()/1000L){runOnUiThread(()->{redeemPanel.setVisibility(View.GONE);vipCard.setVisibility(View.VISIBLE);vipExpiry.setText("Expires  "+date(x));vipRemaining.setText(left(x)+" remaining");});show("Membership active for "+l);}
  else if("expired".equalsIgnoreCase(st)){inactive();show("MEMBERSHIP EXPIRED\n\nExpired: "+date(x)+"\nRedeem a new JPANL code to reactivate.");}
  else{inactive();show("MEMBERSHIP NOT ACTIVATED\n\nEnter a JPANL code to activate this garden account.");}
 }catch(Exception e){inactive();show("STATUS CHECK FAILED\n\n"+e.getMessage());}}
 void inactive(){runOnUiThread(()->{redeemPanel.setVisibility(View.VISIBLE);vipCard.setVisibility(View.GONE);});}
 String date(long x){SimpleDateFormat f=new SimpleDateFormat("MMMM d, yyyy h:mm a",Locale.US);f.setTimeZone(TimeZone.getDefault());return f.format(new Date(x*1000L));}
 String left(long x){long s=x-System.currentTimeMillis()/1000L;if(s<=0)return"Expired";return(s/86400)+" days, "+((s%86400)/3600)+" hours, "+((s%3600)/60)+" minutes";}
 HttpsURLConnection con(String p)throws Exception{HttpsURLConnection c=(HttpsURLConnection)new URL(BASE+p).openConnection();c.setConnectTimeout(15000);c.setReadTimeout(15000);c.setRequestProperty("Accept","application/json");return c;}
 JSONObject post(String p,JSONObject b)throws Exception{HttpsURLConnection c=con(p);c.setRequestMethod("POST");c.setDoOutput(true);c.setRequestProperty("Content-Type","application/json");byte[] z=b.toString().getBytes(StandardCharsets.UTF_8);try(OutputStream o=c.getOutputStream()){o.write(z);}return resp(c);}
 JSONObject get(String p)throws Exception{HttpsURLConnection c=con(p);c.setRequestMethod("GET");return resp(c);}
 JSONObject resp(HttpsURLConnection c)throws Exception{int n=c.getResponseCode();InputStream in=n>=200&&n<300?c.getInputStream():c.getErrorStream();BufferedReader r=new BufferedReader(new InputStreamReader(in,StandardCharsets.UTF_8));StringBuilder b=new StringBuilder();String q;while((q=r.readLine())!=null)b.append(q);JSONObject j=new JSONObject(b.toString());if(n<200||n>=300)throw new Exception("HTTP "+n+": "+j.optString("message",j.optString("state","request failed")));return j;}
 void busy(String s){runOnUiThread(()->{redeem.setEnabled(false);status.setEnabled(false);output.setText(s);});}
 void show(String s){runOnUiThread(()->{redeem.setEnabled(true);status.setEnabled(true);output.setText(s);});}
}