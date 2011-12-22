// Bib2Postgresql
// Dave Parker
// 2005
// Based on example code supplied with javabib (henkel@cs.colorado.edu)

import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.InputStreamReader;
import java.io.IOException;
import java.util.Iterator;
import java.sql.*;

import bibtex.*;
import bibtex.dom.BibtexEntry;
import bibtex.dom.BibtexFile;
import bibtex.dom.BibtexString;
import bibtex.parser.BibtexParser;
import bibtex.parser.ParseException;
import bibtex.expansions.MacroReferenceExpander;
import bibtex.expansions.ExpansionException;

public class Bib2Postgresql
{
	public static void main(String args[])
	{
		if (args.length < 3 || args.length > 4) {
			System.out.println("Usage: java Bib2Postgresql <db_host> <db_user> <db_name> <db_passwd> <db_table>");
			return;
		}
		String table = (args.length > 3) ? args[4] : "bib_items";
		if (!table.matches("[A-Za-z0-9_]*")) {
			System.out.println("Error: Invalid table name \""+table+"\"");
			System.exit(1);
		}
		new Bib2Postgresql().run(args[0], args[1], args[2], args[3], table);
	}
	
	public void run(String dbhost, String dbuser, String dbname, String dbpasswd, String dbtable)
	{
		Connection conn = null;
		PreparedStatement pstmt = null;
		InputStreamReader in = null;
		//FileReader in = null;
		BibtexParser parser = null;
		BibtexFile bfile = null;
		MacroReferenceExpander expander = null;
		String bibtex_fields[] = {"address", "author", "booktitle", "edition", "editor", "institution", "journal", "month", "note", "number", "organization", "pages", "publisher", "school", "series", "title", "type", "volume", "year", "url"};
		String months[] = {"january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"};
		String months_short[] = {"jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"};
		
		// Connect to database
		try {
			Class.forName("org.postgresql.Driver");
			conn = DriverManager.getConnection("jdbc:postgresql://"+dbhost+"/"+dbname, dbuser, dbpasswd);
		} catch (ClassNotFoundException e) {
			System.out.println("Error: Could not load database drivers.");
			System.exit(1);
		} catch (SQLException e) {
			System.out.println("Error: Could not connect to database.");
			System.exit(1);
		}
		
		// Prepare SQL statement
		// (note: doing it this way means character escaping is handled automatically)
		try {
			pstmt = conn.prepareStatement("INSERT INTO "+dbtable+" (key, type, address, author, booktitle, edition, editor, institution, journal, month, note, number, organization, pages, publisher, school, series, title, type2, volume, year, url) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		} catch (SQLException e) {
			System.out.println(e);
			System.exit(1);
		}
		
		// Open/parse bibtex file
		try {
			in = new InputStreamReader(System.in);
			//in = new FileReader(file);
			parser = new BibtexParser(false);
			bfile = new BibtexFile();
			// Expand macros
			expander =  new MacroReferenceExpander(true, true, false, true);
			expander.expand(bfile);
			if (expander.getExceptions() != null) if (expander.getExceptions().length > 0) System.out.println(expander.getExceptions()[0]);
			parser.parse(bfile, in);
		} catch (ExpansionException e) {
			e.printStackTrace();
		} catch (ParseException e) {
			e.printStackTrace();
		} catch (IOException e) {
			e.printStackTrace();
		}
		
		int i, j, n, month, year, count;
		String s, s2;
		BibtexString bs;
		String key = "", type;
		
		// Go through bibtex items
		count = 0;
		for (Iterator it = bfile.getEntries().iterator(); it.hasNext();) {
			
			Object potentialEntry = it.next();
			if (!(potentialEntry instanceof BibtexEntry)) continue;
			BibtexEntry entry = (BibtexEntry) potentialEntry;
			
			try {
				pstmt.clearParameters();
				// Key
				key = "";
				key = entry.getEntryKey();
				if (key==null || key.equals("")) throw new Exception("Missing key");
				pstmt.setString(1, key) ;
				// Type
				type = "";
				type = entry.getEntryType();
				if (type==null || type.equals("")) throw new Exception("Missing type");
				pstmt.setString(2, type) ;
				// Other fields
				n = bibtex_fields.length;
				for (i = 0; i < n; i++) {
					bs = (BibtexString)entry.getFieldValue(bibtex_fields[i]);
					s = (bs != null) ? bs.getContent() : "";
					s = s.replaceAll("\\{", "");
					s = s.replaceAll("\\}", "");
					s = s.replaceAll("--", "-");
					// Author
					if (i == 1) {
						String tokens[] = s.split("\\s+and\\s+");
						s2 = "";
						if (tokens.length > 0) s2 += tokens[0];
						for(j = 1 ; j < tokens.length-1; j++) {
							s2 += ", " + tokens[j];
						}
						if (tokens.length > 1) s2 += " and " + tokens[tokens.length-1];
						pstmt.setString(3+i, s2) ;
					}
					// Month
					else if (i == 7) {
						month = 13;
						s = s.toLowerCase();
						for (j = 1; j <= 12; j++) if (s.equals(months[j-1]) || s.equals(months_short[j-1])) month = j;
						pstmt.setInt(3+i, month);
					}
					// Year
					else if (i == 18) {
						year = 0;
						try {
							year = Integer.parseInt(s);
							pstmt.setInt(3+i, year);
						} catch (NumberFormatException e) {
							pstmt.setNull(3+i, java.sql.Types.INTEGER);
						}
					}
					// Other
					else {
						pstmt.setString(3+i, s) ;
					}
				}
				
				// Execute SQL to add item to database
				//System.out.println(pstmt);
				j = pstmt.executeUpdate() ;
				if (j != 1) {
					System.out.println("Skipping \""+key+"\" (unknown error)");
				}
				count += j;
			}
			catch (Exception e) {
				System.out.println("Skipping \""+key+"\" ("+e.getMessage()+")");
			}
		}
		
		System.out.println(count + " bibtex items added to database.");
		
		// Disconnect from database
		try {
			pstmt.close() ;
			conn.close();
		} catch (SQLException e) {
			System.out.println("Error: Could not disconnect from database.");
			System.exit(1);
		}
	}
}
