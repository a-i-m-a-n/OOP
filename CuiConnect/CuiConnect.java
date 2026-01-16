import java.io.*;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.*;

// ============================================================
//                    INPUT VALIDATION (NO CRASH)
// ============================================================
class InputUtil {

    public static final int MAX_ID_LENGTH = 25;
    public static final int MAX_NAME_LENGTH = 50;
    public static final int MAX_EMAIL_LENGTH = 80;
    public static final int MAX_PASS_LENGTH = 40;
    public static final int MAX_TEXT_LENGTH = 250;

    public static String readNonEmpty(Scanner sc, String prompt, int maxLen) {
        while (true) {
            System.out.print(prompt);
            String s = sc.nextLine().trim();
            if (s.isEmpty()) {
                System.out.println("✗ Input cannot be empty.");
                continue;
            }
            if (s.length() > maxLen) {
                System.out.println("✗ Too long. Max length = " + maxLen);
                continue;
            }
            return s;
        }
    }

    public static String readOptional(Scanner sc, String prompt, int maxLen) {
        System.out.print(prompt);
        String s = sc.nextLine().trim();
        if (s.length() > maxLen) {
            System.out.println("✗ Too long, trimmed to " + maxLen + " chars.");
            return s.substring(0, maxLen);
        }
        return s;
    }

    public static String readId(Scanner sc, String prompt) {
        while (true) {
            String id = readNonEmpty(sc, prompt, MAX_ID_LENGTH);
            if (id.length() < 2) {
                System.out.println("✗ ID too short.");
                continue;
            }
            return id;
        }
    }

    public static String readEmail(Scanner sc, String prompt) {
        while (true) {
            String email = readNonEmpty(sc, prompt, MAX_EMAIL_LENGTH);
            if (!email.contains("@") || email.startsWith("@") || email.endsWith("@")) {
                System.out.println("✗ Invalid email format (must contain '@').");
                continue;
            }
            if (email.contains(" ")) {
                System.out.println("✗ Email cannot contain spaces.");
                continue;
            }
            if (!email.endsWith(".com")) {
                System.out.println("✗ Email must end with .com (as required).");
                continue;
            }
            return email;
        }
    }

    public static String readPassword(Scanner sc, String prompt) {
        while (true) {
            String pass = readNonEmpty(sc, prompt, MAX_PASS_LENGTH);
            if (pass.length() < 4) {
                System.out.println("✗ Password too short (min 4 characters).");
                continue;
            }
            return pass;
        }
    }

    public static int readIntRange(Scanner sc, String prompt, int min, int max) {
        while (true) {
            System.out.print(prompt);
            String raw = sc.nextLine().trim();
            if (raw.isEmpty()) {
                System.out.println("✗ Input cannot be empty.");
                continue;
            }
            try {
                int val = Integer.parseInt(raw);
                if (val < min || val > max) {
                    System.out.println("✗ Enter number between " + min + " and " + max);
                    continue;
                }
                return val;
            } catch (NumberFormatException e) {
                System.out.println("✗ Invalid number. Please enter digits only.");
            }
        }
    }

    public static LocalDate readDate(Scanner sc, String prompt) {
        while (true) {
            System.out.print(prompt);
            String s = sc.nextLine().trim();
            if (s.isEmpty()) {
                System.out.println("✗ Date cannot be empty.");
                continue;
            }
            try {
                return LocalDate.parse(s);
            } catch (Exception e) {
                System.out.println("✗ Invalid date format. Use YYYY-MM-DD (example: 2025-12-20).");
            }
        }
    }

    public static boolean readYesNo(Scanner sc, String prompt) {
        while (true) {
            System.out.print(prompt);
            String s = sc.nextLine().trim().toLowerCase();
            if (s.equals("y") || s.equals("yes")) return true;
            if (s.equals("n") || s.equals("no")) return false;
            System.out.println("✗ Enter y/n.");
        }
    }
}

// ============================================================
//                         LOGGING (REAL)
// ============================================================
class SystemLogger {
    private static final String LOG_FILE = "system_logs.txt";

    public static void log(String action) {
        String line = "[" + LocalDateTime.now() + "] " + action;
        try (FileWriter fw = new FileWriter(LOG_FILE, true)) {
            fw.write(line + "\n");
        } catch (IOException e) {
            // silent fail (avoid crashing)
        }
    }

    public static void printLogs() {
        File f = new File(LOG_FILE);
        if (!f.exists()) {
            System.out.println("No logs found yet.");
            return;
        }
        System.out.println("\n=== SYSTEM LOGS (From system_logs.txt) ===");
        try (BufferedReader br = new BufferedReader(new FileReader(f))) {
            String line;
            int count = 0;
            while ((line = br.readLine()) != null) {
                System.out.println(line);
                count++;
            }
            if (count == 0) System.out.println("(empty log file)");
        } catch (IOException e) {
            System.out.println("Error reading logs: " + e.getMessage());
        }
    }
}

// ===== INTERFACES =====
interface Notifiable {
    void sendNotification(String message);
    void markAsRead(String notificationId);
}

interface Searchable<T> {
    List<T> search(String query);
}

interface Joinable {
    boolean join(Student student);
    boolean leave(Student student);
}

// ===== ENUMS =====
enum UserRole {
    STUDENT, SOCIETY_ADMIN, DEPARTMENT_REP, SYSTEM_ADMIN
}

enum AnnouncementType {
    EVENT, GENERAL, DEPARTMENT, URGENT
}

// ===== ABSTRACT CLASSES =====
abstract class User implements Notifiable {
    protected String userId;
    protected String name;
    protected String email;
    protected String password;
    protected UserRole role;
    protected LocalDate registrationDate;
    protected boolean isActive;
    protected List<Notification> notifications;

    public User() {
        this.notifications = new ArrayList<>();
        this.registrationDate = LocalDate.now();
        this.isActive = true;
    }

    public User(String userId, String name, String email, String password) {
        this();
        this.userId = userId;
        this.name = name;
        this.email = email;
        this.password = password;
    }

    public boolean login(String email, String password) {
        return this.email.equals(email) && this.password.equals(password);
    }

    public void logout() {
        System.out.println(name + " logged out successfully.");
        SystemLogger.log(name + " logged out.");
    }

    @Override
    public void sendNotification(String message) {
        Notification notification = new Notification(
                "NOTIF_" + System.currentTimeMillis(),
                "System Notification",
                message,
                this
        );
        notifications.add(notification);
        SystemLogger.log("Notification sent to " + name + ": " + message);
    }

    @Override
    public void markAsRead(String notificationId) {
        for (Notification n : notifications) {
            if (n.notificationId.equals(notificationId)) {
                n.markAsRead();
                break;
            }
        }
    }

    public abstract void viewDashboard();
    public abstract void showMenu(Scanner scanner);

    // Getters
    public String getUserId() { return userId; }
    public String getName() { return name; }
    public String getEmail() { return email; }
    public UserRole getRole() { return role; }
    public boolean isActive() { return isActive; }
    public List<Notification> getNotifications() { return notifications; }
    public String getPassword() { return password; }
}

// ===== CONCRETE USER CLASSES =====
class Student extends User {
    private String studentId;
    private String department;
    private int semester;
    private List<String> skills;
    private List<Society> joinedSocieties;
    private List<Group> joinedGroups;
    private List<Event> eventRSVPs;

    public Student(String studentId, String name, String email, String password,
                   String department, int semester) {
        super("STU_" + studentId, name, email, password);
        this.studentId = studentId;
        this.department = department;
        this.semester = semester;
        this.skills = new ArrayList<>();
        this.joinedSocieties = new ArrayList<>();
        this.joinedGroups = new ArrayList<>();
        this.eventRSVPs = new ArrayList<>();
        this.role = UserRole.STUDENT;
    }

    @Override
    public void viewDashboard() {
        System.out.println("\n=== STUDENT DASHBOARD ===");
        System.out.println("Name: " + name);
        System.out.println("ID: " + studentId);
        System.out.println("Department: " + department);
        System.out.println("Semester: " + semester);
        System.out.println("\n--- Statistics ---");
        System.out.println("Societies Joined: " + joinedSocieties.size());
        System.out.println("Groups Joined: " + joinedGroups.size());
        System.out.println("Events RSVP'd: " + eventRSVPs.size());
        System.out.println("Skills: " + (skills.isEmpty() ? "(none)" : String.join(", ", skills)));
        System.out.println("Unread Notifications: " + getUnreadNotifications().size());
    }

    @Override
    public void showMenu(Scanner scanner) {
        boolean loggedIn = true;

        while (loggedIn) {
            System.out.println("\n=== STUDENT MENU ===");
            System.out.println("1. View Dashboard");
            System.out.println("2. Join a Society");
            System.out.println("3. Leave a Society");
            System.out.println("4. RSVP to Event");
            System.out.println("5. View Joined Societies");
            System.out.println("6. Search Students by Skill");
            System.out.println("7. View Upcoming Events");
            System.out.println("8. Join Study Group");
            System.out.println("9. Send Message in Group");
            System.out.println("10. View Group Messages");
            System.out.println("11. View Notifications");
            System.out.println("12. Add Skill");
            System.out.println("13. Logout");

            int choice = InputUtil.readIntRange(scanner, "Choose option: ", 1, 13);

            switch (choice) {
                case 1 -> viewDashboard();
                case 2 -> joinSocietyMenu(scanner);
                case 3 -> leaveSocietyMenu(scanner);
                case 4 -> rsvpEventMenu(scanner);
                case 5 -> viewJoinedSocieties();
                case 6 -> searchStudentsBySkill(scanner);
                case 7 -> viewUpcomingEvents();
                case 8 -> joinGroupMenu(scanner);
                case 9 -> sendGroupMessage(scanner);
                case 10 -> viewGroupMessages(scanner);
                case 11 -> viewNotifications(scanner);
                case 12 -> addSkillMenu(scanner);
                case 13 ->  {
                    logout();
                    loggedIn = false;
                }
                default-> System.out.println("Invalid choice!");
            }
        }
    }

    // ✅ show societies list, join by number (no name mismatch)
    private void joinSocietyMenu(Scanner scanner) {
        System.out.println("\n=== JOIN SOCIETY ===");
        List<Society> allSocieties = CuiConnect.getInstance().getAllSocieties();

        if (allSocieties.isEmpty()) {
            System.out.println("No societies available.");
            return;
        }

        System.out.println("Available Societies:");
        for (int i = 0; i < allSocieties.size(); i++) {
            Society s = allSocieties.get(i);
            System.out.println((i + 1) + ". " + s.getName() + " (" + s.getCategory() + ")");
        }
        System.out.println("0. Cancel");

        int choice = InputUtil.readIntRange(scanner, "Enter society number to join: ", 0, allSocieties.size());
        if (choice == 0) return;

        Society society = allSocieties.get(choice - 1);

        if (joinedSocieties.contains(society)) {
            System.out.println("You are already a member of this society.");
            return;
        }

        boolean ok = society.join(this);
        if (ok) {
            joinedSocieties.add(society);
            System.out.println("Request sent to Admin. " + society.getName() + "Waiting For Admin Approval. ");
            sendNotification("You joined " + society.getName() + " society");
            SystemLogger.log(name + " requested to join society: " + society.getName());
        } else {
            System.out.println("Could not join society.");
        }
    }

    private void leaveSocietyMenu(Scanner scanner) {
        System.out.println("\n=== LEAVE SOCIETY ===");
        if (joinedSocieties.isEmpty()) {
            System.out.println("You haven't joined any societies.");
            return;
        }

        System.out.println("Your Societies:");
        for (int i = 0; i < joinedSocieties.size(); i++) {
            System.out.println((i + 1) + ". " + joinedSocieties.get(i).getName());
        }
        System.out.println("0. Cancel");

        int choice = InputUtil.readIntRange(scanner, "Enter number to leave: ", 0, joinedSocieties.size());
        if (choice == 0) return;

        Society society = joinedSocieties.get(choice - 1);

        if (society.leave(this)) {
            joinedSocieties.remove(society);
            System.out.println("Left " + society.getName() + " successfully.");
            SystemLogger.log(name + " left society: " + society.getName());
        } else {
            System.out.println("Failed to leave society.");
        }
    }

    private void rsvpEventMenu(Scanner scanner) {
        System.out.println("\n=== RSVP TO EVENT ===");
        List<Event> allEvents = CuiConnect.getInstance().getAllEvents();

        if (allEvents.isEmpty()) {
            System.out.println("No events available.");
            return;
        }

        System.out.println("Available Events:");
        for (int i = 0; i < allEvents.size(); i++) {
            Event event = allEvents.get(i);
            System.out.println((i + 1) + ". " + event.getTitle() + " (Date: " + event.getDate() + ")");
        }
        System.out.println("0. Cancel");

        int choice = InputUtil.readIntRange(scanner, "Enter event number to RSVP: ", 0, allEvents.size());
        if (choice == 0) return;

        Event event = allEvents.get(choice - 1);

        if (RSVPEvent(event)) {
            System.out.println("Successfully RSVP'd to " + event.getTitle());
            SystemLogger.log(name + " RSVP'd to event: " + event.getTitle());
        } else {
            System.out.println("You already RSVP'd to this event.");
        }
    }

    private void viewJoinedSocieties() {
        System.out.println("\n=== JOINED SOCIETIES ===");
        if (joinedSocieties.isEmpty()) {
            System.out.println("No societies joined.");
        } else {
            for (Society society : joinedSocieties) {
                System.out.println("- " + society.getName() + " (" + society.getCategory() + ")");
                System.out.println("  Description: " + society.getDescription());
            }
        }
    }

    public List<Student> searchStudentsBySkill(List<Student> allStudents, String skill) {
        List<Student> result = new ArrayList<>();
        String key = skill.toLowerCase().trim();
        for (Student s : allStudents) {
            if (s.hasSkill(key) && !s.equals(this)) {
                result.add(s);
            }
        }
        return result;
    }

    private void searchStudentsBySkill(Scanner scanner) {
        String skill = InputUtil.readNonEmpty(scanner, "\nEnter skill to search: ", 30);
        List<Student> students = searchStudentsBySkill(CuiConnect.getInstance().getAllStudents(), skill);

        if (students.isEmpty()) {
            System.out.println("No students found with skill: " + skill);
        } else {
            System.out.println("Students with skill '" + skill + "':");
            for (Student s : students) {
                System.out.println("- " + s.getName() + " (" + s.getDepartment() + ")");
            }
        }
    }

    public List<Event> getUpcomingEvents(List<Event> allEvents) {
        List<Event> upcoming = new ArrayList<>();
        LocalDate today = LocalDate.now();
        for (Event e : allEvents) {
            if (!e.getDate().isBefore(today)) {
                upcoming.add(e);
            }
        }
        return upcoming;
    }

    private void viewUpcomingEvents() {
        List<Event> upcoming = getUpcomingEvents(CuiConnect.getInstance().getAllEvents());

        System.out.println("\n=== UPCOMING EVENTS ===");
        if (upcoming.isEmpty()) {
            System.out.println("No upcoming events.");
        } else {
            for (Event e : upcoming) {
                System.out.println("- " + e.getTitle());
                System.out.println("  Date: " + e.getDate() + " | Venue: " + e.getVenue());
                System.out.println("  Organizer: " + e.getOrganizer());
            }
        }
    }

    // ✅ join group: list existing; message clarity; auto-sync joinedGroups list
    private void joinGroupMenu(Scanner scanner) {
        System.out.println("\n=== JOIN STUDY GROUP ===");
        List<Group> groups = CuiConnect.getInstance().getAllGroups();

        if (groups.isEmpty()) {
            System.out.println("No groups available yet.");
        } else {
            System.out.println("Available Groups:");
            for (int i = 0; i < groups.size(); i++) {
                Group g = groups.get(i);
                System.out.println((i + 1) + ". " + g.getName() + " (Members: " + g.getMembers().size() + ")");
            }
        }

        System.out.println("\nOptions:");
        System.out.println("1. Join an existing group");
        System.out.println("2. Create a new group");
        System.out.println("0. Cancel");

        int opt = InputUtil.readIntRange(scanner, "Choose option: ", 0, 2);
        if (opt == 0) return;

        if (opt == 1) {
            if (groups.isEmpty()) {
                System.out.println("No existing groups to join. Create one instead.");
                return;
            }
            int gChoice = InputUtil.readIntRange(scanner, "Enter group number to join: ", 1, groups.size());
            Group group = groups.get(gChoice - 1);

            if (group.getMembers().contains(this)) {
                System.out.println("You are already a member of this group.");
                // ensure student joinedGroups list is consistent:
                if (!joinedGroups.contains(group)) joinedGroups.add(group);
                return;
            }

            if (group.join(this)) {
                if (!joinedGroups.contains(group)) joinedGroups.add(group);
                System.out.println("Joined group: " + group.getName());
                sendNotification("You joined group: " + group.getName());
                SystemLogger.log(name + " joined group: " + group.getName());
            } else {
                System.out.println("Could not join group (unknown reason).");
            }
        } else {
            String groupName = InputUtil.readNonEmpty(scanner, "Enter New Group Name: ", 50);
            String desc = InputUtil.readOptional(scanner, "Enter Group Description (optional): ", 100);
            if (desc.isEmpty()) desc = "Study Group";
            String category = InputUtil.readOptional(scanner, "Enter Category (optional): ", 40);
            if (category.isEmpty()) category = "Academic";

            // prevent duplicate groups with same name (case-insensitive)
            Group existing = CuiConnect.getInstance().findGroupByName(groupName);
            if (existing != null) {
                System.out.println("Group already exists. Joining existing group instead.");
                if (!existing.getMembers().contains(this)) {
                    existing.join(this);
                }
                if (!joinedGroups.contains(existing)) joinedGroups.add(existing);
                return;
            }

            Group group = new Group(groupName, desc, category, this);
            CuiConnect.getInstance().addGroup(group);
            if (!joinedGroups.contains(group)) joinedGroups.add(group);

            System.out.println("Created & joined group: " + groupName);
            SystemLogger.log(name + " created group: " + groupName);
        }
    }

    private void sendGroupMessage(Scanner scanner) {
        syncJoinedGroupsFromSystem();

        if (joinedGroups.isEmpty()) {
            System.out.println("You haven't joined any groups.");
            return;
        }

        System.out.println("\n=== SEND GROUP MESSAGE ===");
        System.out.println("Your Groups:");
        for (int i = 0; i < joinedGroups.size(); i++) {
            System.out.println((i + 1) + ". " + joinedGroups.get(i).getName());
        }
        System.out.println("0. Cancel");

        int groupChoice = InputUtil.readIntRange(scanner, "Select group: ", 0, joinedGroups.size());
        if (groupChoice == 0) return;

        String message = InputUtil.readNonEmpty(scanner, "Enter message: ", InputUtil.MAX_TEXT_LENGTH);

        Group g = joinedGroups.get(groupChoice - 1);
        Message msg = new Message(message, this, g);
        g.postMessage(msg);
        System.out.println("Message sent!");
        SystemLogger.log(name + " posted message in group: " + g.getName());
    }


    private void viewGroupMessages(Scanner scanner) {
        if (joinedGroups.isEmpty()) {
            System.out.println("You haven't joined any groups.");
            return;
        }

        System.out.println("\n=== VIEW GROUP CHAT ===");
        System.out.println("Your Groups:");
        // List groups for selection
        for (int i = 0; i < joinedGroups.size(); i++) {
            Group g = joinedGroups.get(i);
            System.out.println((i + 1) + ". " + g.getName() +
                            " (Messages: " + g.getMessages().size() + ")");
        }
        System.out.println("0. Cancel");

        int groupChoice = InputUtil.readIntRange(scanner, "Select group: ", 0, joinedGroups.size());
        if (groupChoice == 0) return;

        Group selectedGroup = joinedGroups.get(groupChoice - 1);
        List<Message> messages = selectedGroup.getMessages();

        System.out.println("\n--- Chat History for " + selectedGroup.getName() + " ---");

        if (messages.isEmpty()) {
            System.out.println("No messages in this group yet.");
            return;
        }

        // Display the chat history
        for (Message msg : messages) {
            // Format time to HH:MM for cleaner display
            String time = msg.getTimestamp().toLocalTime().toString().substring(0, 5);
            String senderName = msg.getSender().getName();
            
            System.out.println("[" + time + "] " + senderName + ": " + msg.getContent());
        }
        System.out.println("----------------------------------------");
    }
        // keep student list consistent with system groups
        private void syncJoinedGroupsFromSystem() {
            List<Group> all = CuiConnect.getInstance().getAllGroups();
            for (Group g : all) {
                if (g.getMembers().contains(this) && !joinedGroups.contains(g)) {
                    joinedGroups.add(g);
                }
            }
        }

    private void viewNotifications(Scanner scanner) {
        System.out.println("\n=== NOTIFICATIONS ===");
        List<Notification> unread = getUnreadNotifications();
        if (unread.isEmpty()) {
            System.out.println("No unread notifications.");
        } else {
            for (int i = 0; i < unread.size(); i++) {
                System.out.println((i + 1) + ". " + unread.get(i).getContent());
            }
            boolean mark = InputUtil.readYesNo(scanner, "Mark all as read? (y/n): ");
            if (mark) {
                for (Notification n : notifications) n.markAsRead();
                System.out.println("All notifications marked as read.");
                SystemLogger.log(name + " marked notifications as read.");
            }
        }
    }

    private void addSkillMenu(Scanner scanner) {
        String skill = InputUtil.readNonEmpty(scanner, "\nEnter skill to add: ", 30);
        addSkill(skill);
        System.out.println("Skill added: " + skill);
        SystemLogger.log(name + " added skill: " + skill);
    }

    public boolean RSVPEvent(Event event) {
        if (!eventRSVPs.contains(event)) {
            eventRSVPs.add(event);
            event.registerAttendee(this);
            sendNotification("RSVP confirmed for: " + event.getTitle());
            return true;
        }
        return false;
    }

    private boolean hasSkill(String skill) {
        return skills.contains(skill.toLowerCase());
    }

    public void addSkill(String skill) {
        if (skill == null) return;
        String cleaned = skill.trim().toLowerCase();
        if (cleaned.isEmpty()) return;
        if (!skills.contains(cleaned)) {
            skills.add(cleaned);
        }
    }

    public List<Notification> getUnreadNotifications() {
        List<Notification> unread = new ArrayList<>();
        for (Notification n : notifications) {
            if (!n.isRead) unread.add(n);
        }
        return unread;
    }

    // Getters
    public String getStudentId() { return studentId; }
    public String getDepartment() { return department; }
    public int getSemester() { return semester; }
    public List<String> getSkills() { return skills; }
    public List<Society> getJoinedSocieties() { return joinedSocieties; }
    public List<Event> getEventRSVPs() { return eventRSVPs; }
    public List<Group> getJoinedGroups() { return joinedGroups; }
}

class SocietyAdmin extends User {
    private String adminId;
    private List<Society> managedSocieties;

    public SocietyAdmin(String adminId, String name, String email, String password) {
        super("ADMIN_" + adminId, name, email, password);
        this.adminId = adminId;
        this.managedSocieties = new ArrayList<>();
        this.role = UserRole.SOCIETY_ADMIN;
    }

    @Override
    public void viewDashboard() {
        System.out.println("\n=== SOCIETY ADMIN DASHBOARD ===");
        System.out.println("Name: " + name);
        System.out.println("Admin ID: " + adminId);
        System.out.println("\nManaged Societies: " + managedSocieties.size());
        for (Society s : managedSocieties) {
            System.out.println("- " + s.getName() + " (Members: " + s.getMembers().size() + ")");
        }
    }

    @Override
    public void showMenu(Scanner scanner) {
        boolean loggedIn = true;

        while (loggedIn) {
            System.out.println("\n=== SOCIETY ADMIN MENU ===");
            System.out.println("1. View Dashboard");
            System.out.println("2. Create Society");
            System.out.println("3. Manage Society Members");
            System.out.println("4. Create Event");
            System.out.println("5. Post Announcement");
            System.out.println("6. View Society Reports");
            System.out.println("7. Approve Join Requests");
            System.out.println("8. Logout");

            int choice = InputUtil.readIntRange(scanner, "Choose option: ", 1, 8);

            switch (choice) {
                case 1: viewDashboard(); break;
                case 2: createSocietyMenu(scanner); break;
                case 3: manageMembersMenu(scanner); break;
                case 4: createEventMenu(scanner); break;
                case 5: postAnnouncementMenu(scanner); break;
                case 6: viewReportsMenu(); break;
                case 7: approveRequestsMenu(scanner); break;
                case 8:
                    logout();
                    loggedIn = false;
                    break;
                default:
                    System.out.println("Invalid choice!");
            }
        }
    }

    private void createSocietyMenu(Scanner scanner) {
        System.out.println("\n=== CREATE SOCIETY ===");
        String sName = InputUtil.readNonEmpty(scanner, "Society Name: ", 50);
        String desc = InputUtil.readNonEmpty(scanner, "Description: ", 150);
        String category = InputUtil.readNonEmpty(scanner, "Category: ", 40);

        // prevent duplicate societies
        if (CuiConnect.getInstance().findSocietyByName(sName) != null) {
            System.out.println("Society already exists with this name.");
            return;
        }

        Society society = new Society(sName, desc, category, this);
        managedSocieties.add(society);
        CuiConnect.getInstance().addSociety(society);

        System.out.println("Society created successfully!");
        SystemLogger.log(name + " created society: " + sName);
    }

    private void manageMembersMenu(Scanner scanner) {
        if (managedSocieties.isEmpty()) {
            System.out.println("You don't manage any societies.");
            return;
        }

        System.out.println("\n=== MANAGE SOCIETY MEMBERS ===");
        for (int i = 0; i < managedSocieties.size(); i++) {
            System.out.println((i + 1) + ". " + managedSocieties.get(i).getName());
        }
        System.out.println("0. Cancel");

        int choice = InputUtil.readIntRange(scanner, "Select society: ", 0, managedSocieties.size());
        if (choice == 0) return;

        Society society = managedSocieties.get(choice - 1);

        System.out.println("\nSociety: " + society.getName());
        System.out.println("Members (" + society.getMembers().size() + "):");
        if (society.getMembers().isEmpty()) {
            System.out.println("No members yet.");
            return;
        }

        for (Student s : society.getMembers()) {
            System.out.println("- " + s.getName() + " (" + s.getStudentId() + ")");
        }

        System.out.println("\n1. Remove Member");
        System.out.println("2. Back");
        int action = InputUtil.readIntRange(scanner, "Choice: ", 1, 2);
        if (action == 2) return;

        String stuId = InputUtil.readNonEmpty(scanner, "Enter student ID to remove: ", InputUtil.MAX_ID_LENGTH);
        boolean removed = false;
        for (Student s : new ArrayList<>(society.getMembers())) {
            if (s.getStudentId().equalsIgnoreCase(stuId)) {
                society.removeMember(s);
                removed = true;
                System.out.println("Member removed.");
                SystemLogger.log(name + " removed member " + s.getStudentId() + " from society " + society.getName());
                break;
            }
        }
        if (!removed) System.out.println("Student not found in society.");
    }

    private void createEventMenu(Scanner scanner) {
        if (managedSocieties.isEmpty()) {
            System.out.println("You need to create a society first.");
            return;
        }

        System.out.println("\n=== CREATE EVENT ===");
        for (int i = 0; i < managedSocieties.size(); i++) {
            System.out.println((i + 1) + ". " + managedSocieties.get(i).getName());
        }
        System.out.println("0. Cancel");

        int socChoice = InputUtil.readIntRange(scanner, "Select society: ", 0, managedSocieties.size());
        if (socChoice == 0) return;

        String title = InputUtil.readNonEmpty(scanner, "Event Title: ", 60);
        String desc = InputUtil.readNonEmpty(scanner, "Description: ", 200);
        LocalDate date = InputUtil.readDate(scanner, "Date (YYYY-MM-DD): ");
        String venue = InputUtil.readNonEmpty(scanner, "Venue: ", 60);

        Society society = managedSocieties.get(socChoice - 1);
        society.createEvent(title, desc, date, venue);

        Event event = new Event(title, desc, date, venue, society.getName());
        CuiConnect.getInstance().addEvent(event);

        EventAnnouncement announcement = new EventAnnouncement(
                "New Event: " + title,
                desc + "\nDate: " + date + "\nVenue: " + venue,
                this.userId,
                event
        );
        announcement.publish();
        society.postAnnouncement(announcement);

        System.out.println("Event created and announced!");
        SystemLogger.log(name + " created event: " + title + " for society " + society.getName());
    }

    private void postAnnouncementMenu(Scanner scanner) {
        if (managedSocieties.isEmpty()) {
            System.out.println("You need to create a society first.");
            return;
        }

        System.out.println("\n=== POST ANNOUNCEMENT ===");
        for (int i = 0; i < managedSocieties.size(); i++) {
            System.out.println((i + 1) + ". " + managedSocieties.get(i).getName());
        }
        System.out.println("0. Cancel");

        int socChoice = InputUtil.readIntRange(scanner, "Select society: ", 0, managedSocieties.size());
        if (socChoice == 0) return;

        String title = InputUtil.readNonEmpty(scanner, "Announcement Title: ", 60);
        String content = InputUtil.readNonEmpty(scanner, "Content: ", 250);

        Society society = managedSocieties.get(socChoice - 1);

        GeneralAnnouncement announcement = new GeneralAnnouncement(title, content, this.userId);
        announcement.publish();
        society.postAnnouncement(announcement);

        System.out.println("Announcement posted!");
        SystemLogger.log(name + " posted announcement: " + title + " in society " + society.getName());
    }

    private void viewReportsMenu() {
        System.out.println("\n=== SOCIETY REPORTS ===");
        for (Society society : managedSocieties) {
            System.out.println("\nSociety: " + society.getName());
            System.out.println("Total Members: " + society.getMembers().size());
            System.out.println("Total Events: " + society.getEvents().size());
            System.out.println("Upcoming Events: " + society.getUpcomingEvents().size());
        }
    }

     private void approveRequestsMenu(Scanner scanner) {
        if (managedSocieties.isEmpty()) {
            System.out.println("You don't manage any societies.");
            return;
        }

        System.out.println("\n=== PENDING JOIN REQUESTS ===");
        for (int i = 0; i < managedSocieties.size(); i++) {
            Society s = managedSocieties.get(i);
            System.out.println((i + 1) + ". " + s.getName() + " (" + s.getPendingRequests().size() + " pending)");
        }
        System.out.println("0. Back");

        int choice = InputUtil.readIntRange(scanner, "Select society to manage requests: ", 0, managedSocieties.size());
        if (choice == 0) return;

        Society society = managedSocieties.get(choice - 1);
        List<Student> pending = society.getPendingRequests();

        if (pending.isEmpty()) {
            System.out.println("No pending requests for this society.");
            return;
        }

        for (int i = 0; i < pending.size(); i++) {
            Student s = pending.get(i);
            System.out.println((i + 1) + ". " + s.getName() + " (" + s.getStudentId() + ")");
        }
        
        int stuChoice = InputUtil.readIntRange(scanner, "Select student to process (0 to cancel): ", 0, pending.size());
        if (stuChoice == 0) return;

        Student targetStudent = pending.get(stuChoice - 1);
        
        System.out.println("1. Approve");
        System.out.println("2. Reject");
        int action = InputUtil.readIntRange(scanner, "Action: ", 1, 2);

        if (action == 1) {
            society.approveStudent(targetStudent);
            System.out.println("Student approved!");
            SystemLogger.log(name + " approved " + targetStudent.getName() + " for " + society.getName());
        } else {
            society.rejectStudent(targetStudent);
            System.out.println("Request rejected.");
            SystemLogger.log(name + " rejected " + targetStudent.getName() + " for " + society.getName());
        }
    }

    public void addManagedSociety(Society society) {
        managedSocieties.add(society);
    }

    public String getAdminId() { return adminId; }
    public List<Society> getManagedSocieties() { return managedSocieties; }
}

class DepartmentRepresentative extends User {
    private String deptId;
    private String department;

    public DepartmentRepresentative(String deptId, String name, String email, String password, String department) {
        super("DEPT_" + deptId, name, email, password);
        this.deptId = deptId;
        this.department = department;
        this.role = UserRole.DEPARTMENT_REP;
    }

    @Override
    public void viewDashboard() {
        System.out.println("\n=== DEPARTMENT REPRESENTATIVE DASHBOARD ===");
        System.out.println("Name: " + name);
        System.out.println("Department: " + department);
        System.out.println("Rep ID: " + deptId);

        List<Student> deptStudents = getDepartmentStudents(CuiConnect.getInstance().getAllStudents());
        System.out.println("\nDepartment Statistics:");
        System.out.println("Total Students: " + deptStudents.size());

        Map<Integer, Integer> semesterCount = new HashMap<>();
        for (Student s : deptStudents) {
            semesterCount.put(s.getSemester(), semesterCount.getOrDefault(s.getSemester(), 0) + 1);
        }
        System.out.println("Students by Semester:");
        for (Map.Entry<Integer, Integer> entry : semesterCount.entrySet()) {
            System.out.println("  Semester " + entry.getKey() + ": " + entry.getValue());
        }
    }

    @Override
    public void showMenu(Scanner scanner) {
        boolean loggedIn = true;

        while (loggedIn) {
            System.out.println("\n=== DEPARTMENT REP MENU ===");
            System.out.println("1. View Dashboard");
            System.out.println("2. Post Department Announcement");
            System.out.println("3. View Department Students");
            System.out.println("4. Create Department Event");
            System.out.println("5. Logout");

            int choice = InputUtil.readIntRange(scanner, "Choose option: ", 1, 5);

            switch (choice) {
                case 1: viewDashboard(); break;
                case 2: postDeptAnnouncement(scanner); break;
                case 3: viewDeptStudents(); break;
                case 4: createDeptEvent(scanner); break;
                case 5:
                    logout();
                    loggedIn = false;
                    break;
                default:
                    System.out.println("Invalid choice!");
            }
        }
    }

    private void postDeptAnnouncement(Scanner scanner) {
        System.out.println("\n=== POST DEPARTMENT ANNOUNCEMENT ===");
        String title = InputUtil.readNonEmpty(scanner, "Title: ", 60);
        String content = InputUtil.readNonEmpty(scanner, "Content: ", 250);

        GeneralAnnouncement announcement = new GeneralAnnouncement(title, content, this.userId);
        announcement.setAnnouncementType("DEPARTMENT");
        announcement.publish();

        List<Student> deptStudents = getDepartmentStudents(CuiConnect.getInstance().getAllStudents());
        for (Student s : deptStudents) {
            s.sendNotification("New department announcement: " + title);
        }

        System.out.println("Announcement posted to " + deptStudents.size() + " students!");
        SystemLogger.log(name + " posted department announcement: " + title);
    }

    private void viewDeptStudents() {
        List<Student> deptStudents = getDepartmentStudents(CuiConnect.getInstance().getAllStudents());

        System.out.println("\n=== DEPARTMENT STUDENTS ===");
        System.out.println("Department: " + department);
        System.out.println("Total: " + deptStudents.size());

        for (Student s : deptStudents) {
            System.out.println("\n- " + s.getName() + " (" + s.getStudentId() + ")");
            System.out.println("  Semester: " + s.getSemester());
            System.out.println("  Skills: " + (s.getSkills().isEmpty() ? "(none)" : String.join(", ", s.getSkills())));
            System.out.println("  Societies: " + s.getJoinedSocieties().size());
        }
    }

    private void createDeptEvent(Scanner scanner) {
        System.out.println("\n=== CREATE DEPARTMENT EVENT ===");
        String title = InputUtil.readNonEmpty(scanner, "Event Title: ", 60);
        String desc = InputUtil.readNonEmpty(scanner, "Description: ", 200);
        LocalDate date = InputUtil.readDate(scanner, "Date (YYYY-MM-DD): ");
        String venue = InputUtil.readNonEmpty(scanner, "Venue: ", 60);

        Event event = new Event(title, desc, date, venue, department + " Department");
        CuiConnect.getInstance().addEvent(event);

        List<Student> deptStudents = getDepartmentStudents(CuiConnect.getInstance().getAllStudents());
        for (Student s : deptStudents) {
            s.sendNotification("New department event: " + title);
        }

        System.out.println("Event created and announced to " + deptStudents.size() + " students!");
        SystemLogger.log(name + " created department event: " + title);
    }

    public List<Student> getDepartmentStudents(List<Student> allStudents) {
        List<Student> deptStudents = new ArrayList<>();
        for (Student s : allStudents) {
            if (s.getDepartment().equalsIgnoreCase(department)) {
                deptStudents.add(s);
            }
        }
        return deptStudents;
    }

    public String getDeptId() { return deptId; }
    public String getDepartment() { return department; }
}

class SystemAdmin extends User {
    public SystemAdmin(String adminId, String name, String email, String password) {
        super("SYS_" + adminId, name, email, password);
        this.role = UserRole.SYSTEM_ADMIN;
    }

    @Override
    public void viewDashboard() {
        System.out.println("\n=== SYSTEM ADMIN DASHBOARD ===");
        System.out.println("Name: " + name);
        System.out.println("Role: System Administrator");

        CuiConnect system = CuiConnect.getInstance();
        System.out.println("\n=== SYSTEM STATISTICS ===");
        System.out.println("Total Users: " + system.getAllUsers().size());
        System.out.println("Total Students: " + system.getAllStudents().size());
        System.out.println("Total Societies: " + system.getAllSocieties().size());
        System.out.println("Total Events: " + system.getAllEvents().size());
        System.out.println("Total Groups: " + system.getAllGroups().size());
        System.out.println("Active Users: " + system.getActiveUsersCount());
    }

    @Override
    public void showMenu(Scanner scanner) {
        boolean loggedIn = true;

        while (loggedIn) {
            System.out.println("\n=== SYSTEM ADMIN MENU ===");
            System.out.println("1. View Dashboard");
            System.out.println("2. View All Users");
            System.out.println("3. Activate/Deactivate User");
            System.out.println("4. Backup System Data");
            System.out.println("5. Generate System Report");
            System.out.println("6. View System Logs");
            System.out.println("7. Manage All Societies");
            System.out.println("8. Logout");

            int choice = InputUtil.readIntRange(scanner, "Choose option: ", 1, 8);

            switch (choice) {
                case 1: viewDashboard(); break;
                case 2: viewAllUsers(); break;
                case 3: manageUserStatus(scanner); break;
                case 4: backupData(); break;
                case 5: generateReport(); break;
                case 6: viewLogs(); break;
                case 7: manageSocieties(scanner); break;
                case 8:
                    logout();
                    loggedIn = false;
                    break;
                default:
                    System.out.println("Invalid choice!");
            }
        }
    }

    private void viewAllUsers() {
        List<User> allUsers = CuiConnect.getInstance().getAllUsers();

        System.out.println("\n=== ALL SYSTEM USERS ===");
        System.out.printf("%-20s %-30s %-18s %-10s%n", "Name", "Email", "Role", "Status");
        System.out.println("-".repeat(85));

        for (User u : allUsers) {
            System.out.printf("%-20s %-30s %-18s %-10s%n",
                    u.getName(),
                    u.getEmail(),
                    u.getRole(),
                    u.isActive() ? "Active" : "Inactive");
        }
        SystemLogger.log(name + " viewed all users.");
    }

    private void manageUserStatus(Scanner scanner) {
        System.out.println("\n=== MANAGE USER STATUS ===");
        List<User> allUsers = CuiConnect.getInstance().getAllUsers();

        for (int i = 0; i < allUsers.size(); i++) {
            User u = allUsers.get(i);
            System.out.println((i + 1) + ". " + u.getName() + " (" + u.getRole() + ") - " +
                    (u.isActive() ? "Active" : "Inactive"));
        }
        System.out.println("0. Cancel");

        int choice = InputUtil.readIntRange(scanner, "Select user (0 to cancel): ", 0, allUsers.size());
        if (choice == 0) return;

        User user = allUsers.get(choice - 1);
        CuiConnect.getInstance().saveUsersToDisk();

        user.isActive = !user.isActive;
        System.out.println("User " + user.getName() + " is now " + (user.isActive ? "ACTIVE" : "INACTIVE"));
        SystemLogger.log(name + " toggled status for " + user.getName() + " to " + (user.isActive ? "ACTIVE" : "INACTIVE"));
    }

    // ✅ Backup truly writes + includes full info (still same option)
    private void backupData() {
        String filename = "backup_" + System.currentTimeMillis() + ".txt";
        FileHandler fileHandler = new FileHandler(filename);

        CuiConnect system = CuiConnect.getInstance();
        StringBuilder backup = new StringBuilder();

        backup.append("=== CUICONNECT BACKUP ").append(LocalDateTime.now()).append(" ===\n\n");
        backup.append("Total Users: ").append(system.getAllUsers().size()).append("\n");
        backup.append("Total Students: ").append(system.getAllStudents().size()).append("\n");
        backup.append("Total Societies: ").append(system.getAllSocieties().size()).append("\n");
        backup.append("Total Events: ").append(system.getAllEvents().size()).append("\n");
        backup.append("Total Groups: ").append(system.getAllGroups().size()).append("\n\n");

        backup.append("=== USERS LIST ===\n");
        for (User u : system.getAllUsers()) {
            backup.append(u.getUserId()).append(" | ").append(u.getName())
                    .append(" | ").append(u.getEmail())
                    .append(" | ").append(u.getRole())
                    .append(" | ").append(u.isActive() ? "Active" : "Inactive")
                    .append("\n");
        }
        backup.append("\n=== SOCIETIES ===\n");
        for (Society s : system.getAllSocieties()) {
            backup.append(s.getSocietyId()).append(" | ").append(s.getName())
                    .append(" | Members: ").append(s.getMembers().size())
                    .append(" | Events: ").append(s.getEvents().size())
                    .append("\n");
        }
        backup.append("\n=== GROUPS ===\n");
        for (Group g : system.getAllGroups()) {
            backup.append(g.getGroupId()).append(" | ").append(g.getName())
                    .append(" | Members: ").append(g.getMembers().size())
                    .append(" | Messages: ").append(g.getMessages().size())
                    .append("\n");
        }

        boolean ok = fileHandler.saveToFile(backup.toString());
        if (ok) {
            System.out.println("Backup created successfully!");
            System.out.println("Saved as: " + new File(filename).getAbsolutePath());
            SystemLogger.log(name + " created backup file: " + filename);
        } else {
            System.out.println("Backup failed.");
        }
    }

    private void generateReport() {
        System.out.println("\n=== SYSTEM REPORT ===");

        CuiConnect system = CuiConnect.getInstance();
        int totalUsers = system.getAllUsers().size();
        int activeUsers = system.getActiveUsersCount();

        System.out.println("1. User Statistics:");
        System.out.println("   Total Users: " + totalUsers);
        System.out.println("   Active Users: " + activeUsers);
        System.out.println("   Inactive Users: " + (totalUsers - activeUsers));

        System.out.println("\n2. Society Statistics:");
        System.out.println("   Total Societies: " + system.getAllSocieties().size());
        for (Society s : system.getAllSocieties()) {
            System.out.println("   - " + s.getName() + ": " + s.getMembers().size() + " members");
        }

        System.out.println("\n3. Event Statistics:");
        System.out.println("   Total Events: " + system.getAllEvents().size());

        System.out.println("\nReport generated successfully!");
        SystemLogger.log(name + " generated system report.");
    }

    // ✅ now real logs
    private void viewLogs() {
        SystemLogger.printLogs();
        SystemLogger.log(name + " viewed system logs.");
    }

    private void manageSocieties(Scanner scanner) {
        System.out.println("\n=== MANAGE ALL SOCIETIES ===");
        List<Society> societies = CuiConnect.getInstance().getAllSocieties();

        if (societies.isEmpty()) {
            System.out.println("No societies in system.");
            return;
        }

        for (int i = 0; i < societies.size(); i++) {
            Society s = societies.get(i);
            System.out.println((i + 1) + ". " + s.getName());
            System.out.println("   Admin: " + s.getAdmin().getName());
            System.out.println("   Members: " + s.getMembers().size());
            System.out.println("   Events: " + s.getEvents().size());
        }

        System.out.println("\n1. View Society Details");
        System.out.println("2. Back");
        int choice = InputUtil.readIntRange(scanner, "Choice: ", 1, 2);
        if (choice == 2) return;

        int socNum = InputUtil.readIntRange(scanner, "Enter society number: ", 1, societies.size());
        Society s = societies.get(socNum - 1);
        System.out.println("\n=== SOCIETY DETAILS ===");
        System.out.println("Name: " + s.getName());
        System.out.println("Category: " + s.getCategory());
        System.out.println("Description: " + s.getDescription());
        System.out.println("Admin: " + s.getAdmin().getName());

        System.out.println("\nMembers:");
        if (s.getMembers().isEmpty()) {
            System.out.println("(none)");
        } else {
            for (Student member : s.getMembers()) {
                System.out.println("- " + member.getName());
            }
        }
        SystemLogger.log(name + " viewed society details: " + s.getName());
    }
}

// ===== ANNOUNCEMENT CLASSES =====
abstract class Announcement {
    protected String announcementId;
    protected String title;
    protected String content;
    protected String createdBy;
    protected LocalDateTime creationDate;
    protected boolean isPublished;

    public Announcement(String title, String content, String createdBy) {
        this.announcementId = "ANN_" + System.currentTimeMillis();
        this.title = title;
        this.content = content;
        this.createdBy = createdBy;
        this.creationDate = LocalDateTime.now();
        this.isPublished = false;
    }

    public void publish() { this.isPublished = true; }

    public abstract String getAnnouncementType();

    public String getAnnouncementId() { return announcementId; }
    public String getTitle() { return title; }
    public String getContent() { return content; }
    public String getCreatedBy() { return createdBy; }
    public boolean isPublished() { return isPublished; }
}

class EventAnnouncement extends Announcement {
    private Event relatedEvent;

    public EventAnnouncement(String title, String content, String createdBy, Event event) {
        super(title, content, createdBy);
        this.relatedEvent = event;
    }

    @Override
    public String getAnnouncementType() { return "EVENT"; }

    public Event getEvent() { return relatedEvent; }
}

class GeneralAnnouncement extends Announcement {
    private String announcementType;

    public GeneralAnnouncement(String title, String content, String createdBy) {
        super(title, content, createdBy);
        this.announcementType = "GENERAL";
    }

    @Override
    public String getAnnouncementType() { return announcementType; }

    public void setAnnouncementType(String type) { this.announcementType = type; }
}

// ===== SOCIETY CLASS =====
class Society implements Joinable {
    private String societyId;
    private String name;
    private String description;
    private String category;
    private List<Student> members;
    private List<Event> events;
    private List<Announcement> announcements;
    private SocietyAdmin admin;

    public Society(String name, String description, String category, SocietyAdmin admin) {
        this.societyId = "SOC_" + System.currentTimeMillis();
        this.name = name;
        this.description = description;
        this.category = category;
        this.members = new ArrayList<>();
        this.events = new ArrayList<>();
        this.announcements = new ArrayList<>();
        this.admin = admin;
    }

    final private List<Student> pendingRequests = new ArrayList<>();
    public List<Student> getPendingRequests() { return pendingRequests; }

    @Override
    public boolean join(Student student) {
            if (!members.contains(student) && !pendingRequests.contains(student)) {
                pendingRequests.add(student);
                admin.sendNotification("New join request for " + name + " from " + student.getName());
                return true; 
            }
            return false;
        }

    

        public void approveStudent(Student student) {
            if (pendingRequests.remove(student)) {
                members.add(student);
                student.getJoinedSocieties().add(this); // Sync to student's list
                student.sendNotification("Your request to join " + name + " has been APPROVED!");
            }
        }

        public void rejectStudent(Student student) {
            if (pendingRequests.remove(student)) {
                student.sendNotification("Your request to join " + name + " was declined.");
            }
        }

    @Override
    public boolean leave(Student student) {
        return members.remove(student);
    }


    public boolean createEvent(String title, String description, LocalDate date, String venue) {
        Event event = new Event(title, description, date, venue, this.name);
        events.add(event);
        return true;
    }

    public boolean postAnnouncement(Announcement announcement) {
        announcements.add(announcement);
        for (Student member : members) {
            member.sendNotification("New announcement from " + name + ": " + announcement.getTitle());
        }
        return true;
    }

    public boolean addMember(Student student) { return join(student); }
    public boolean removeMember(Student student) { return leave(student); }

    public List<Event> getUpcomingEvents() {
        List<Event> upcoming = new ArrayList<>();
        LocalDate today = LocalDate.now();
        for (Event e : events) {
            if (!e.getDate().isBefore(today)) {
                upcoming.add(e);
            }
        }
        return upcoming;
    }

    public String getSocietyId() { return societyId; }
    public String getName() { return name; }
    public String getDescription() { return description; }
    public String getCategory() { return category; }
    public List<Student> getMembers() { return members; }
    public List<Event> getEvents() { return events; }
    public SocietyAdmin getAdmin() { return admin; }
}

// ===== EVENT CLASS =====
class Event {
    private String eventId;
    private String title;
    private String description;
    private LocalDate date;
    private String venue;
    private String organizer;
    private List<Student> attendees;

    public Event(String title, String description, LocalDate date, String venue, String organizer) {
        this.eventId = "EVT_" + System.currentTimeMillis();
        this.title = title;
        this.description = description;
        this.date = date;
        this.venue = venue;
        this.organizer = organizer;
        this.attendees = new ArrayList<>();
    }

    public boolean registerAttendee(Student student) {
        if (!attendees.contains(student)) {
            attendees.add(student);
            return true;
        }
        return false;
    }

    public String getEventId() { return eventId; }
    public String getTitle() { return title; }
    public String getDescription() { return description; }
    public LocalDate getDate() { return date; }
    public String getVenue() { return venue; }
    public String getOrganizer() { return organizer; }
    public List<Student> getAttendees() { return attendees; }
}

// ===== GROUP CLASS =====
class Group implements Joinable {
    private String groupId;
    private String name;
    private String description;
    private String category;
    private List<Student> members;
    private List<Message> messages;

    public Group(String name, String description, String category, Student creator) {
        this.groupId = "GRP_" + System.currentTimeMillis();
        this.name = name;
        this.description = description;
        this.category = category;
        this.members = new ArrayList<>();
        this.messages = new ArrayList<>();
        this.members.add(creator);
    }

    @Override
    public boolean join(Student student) {
        if (!members.contains(student)) {
            members.add(student);
            return true;
        }
        return false;
    }

    @Override
    public boolean leave(Student student) {
        return members.remove(student);
    }

    public boolean postMessage(Message message) {
        messages.add(message);
        return true;
    }

    public String getGroupId() { return groupId; }
    public String getName() { return name; }
    public List<Student> getMembers() { return members; }
    public List<Message> getMessages() { return messages; }
}

// ===== MESSAGE CLASS =====
class Message {
    private String messageId;
    private String content;
    private LocalDateTime timestamp;
    private Student sender;
    private Object receiver;

    public Message(String content, Student sender, Object receiver) {
        this.messageId = "MSG_" + System.currentTimeMillis();
        this.content = content;
        this.timestamp = LocalDateTime.now();
        this.sender = sender;
        this.receiver = receiver;
    }

    public String getMessageId() { return messageId; }
    public String getContent() { return content; }
    public Student getSender() { return sender; }
    public LocalDateTime getTimestamp() { return timestamp; }
}

// ===== NOTIFICATION CLASS =====
class Notification {
    String notificationId;
    String title;
    String content;
    LocalDateTime timestamp;
    User recipient;
    boolean isRead;

    public Notification(String notificationId, String title, String content, User recipient) {
        this.notificationId = notificationId;
        this.title = title;
        this.content = content;
        this.timestamp = LocalDateTime.now();
        this.recipient = recipient;
        this.isRead = false;
    }

    public void markAsRead() { this.isRead = true; }
    public String getContent() { return content; }
    public boolean isRead() { return isRead; }
}

// ===== FILE HANDLER =====
class FileHandler {
    private String fileName;

    public FileHandler(String fileName) {
        this.fileName = fileName;
    }

    // append (existing behavior)
    public boolean saveToFile(String data) {
        try (FileWriter writer = new FileWriter(fileName, true)) {
            writer.write(data);
            writer.write("\n");
            return true;
        } catch (IOException e) {
            System.out.println("Error saving file: " + e.getMessage());
            return false;
        }
    }

    // overwrite (for DB saving)
    public boolean overwriteToFile(String data) {
        try (FileWriter writer = new FileWriter(fileName, false)) {
            writer.write(data);
            writer.write("\n");
            return true;
        } catch (IOException e) {
            System.out.println("Error writing file: " + e.getMessage());
            return false;
        }
    }

    // read all lines (for DB loading)
    public List<String> readLines() {
        List<String> lines = new ArrayList<>();
        File f = new File(fileName);
        if (!f.exists()) return lines;

        try (BufferedReader br = new BufferedReader(new FileReader(f))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (!line.isEmpty()) lines.add(line);
            }
        } catch (IOException e) {
            System.out.println("Error reading file: " + e.getMessage());
        }
        return lines;
    }
}

// ============================================================
//                    MAIN SYSTEM (SINGLETON)
// ============================================================
public class CuiConnect {
    private static CuiConnect instance;
    private List<User> allUsers;
    private List<Student> students;
    private List<SocietyAdmin> societyAdmins;
    private List<DepartmentRepresentative> departmentReps;
    private List<SystemAdmin> systemAdmins;
    private List<Society> societies;
    private List<Event> events;
    private List<Group> groups;

    private CuiConnect() {
        allUsers = new ArrayList<>();
        students = new ArrayList<>();
        societyAdmins = new ArrayList<>();
        departmentReps = new ArrayList<>();
        systemAdmins = new ArrayList<>();
        societies = new ArrayList<>();
        events = new ArrayList<>();
        groups = new ArrayList<>();
    }

    public static CuiConnect getInstance() {
        if (instance == null) instance = new CuiConnect();
        return instance;
    }

    public void startSystem(Scanner scanner) {
        boolean systemRunning = true;

        while (systemRunning) {
            System.out.println("\n=== WELCOME TO CUICONNECT ===");
            System.out.println("1. Register");
            System.out.println("2. Login");
            System.out.println("3. Exit System");

            int choice = InputUtil.readIntRange(scanner, "Enter choice: ", 1, 3);

            switch (choice) {
                case 1: registerUser(scanner); break;
                case 2: loginUser(scanner); break;
                case 3:
                        saveUsersToDisk();
                    systemRunning = false;
                    System.out.println("Thank you for using CuiConnect!");
                    SystemLogger.log("System closed.");
                    break;
            }
        }
    }

    private void registerUser(Scanner scanner) {
        System.out.println("\n=== REGISTRATION ===");
        System.out.println("Select user type:");
        System.out.println("1. Student");
        System.out.println("2. Society Admin");
        System.out.println("3. Department Representative");
        System.out.println("4. System Admin");

        int userType = InputUtil.readIntRange(scanner, "Enter choice: ", 1, 4);

        String name = InputUtil.readNonEmpty(scanner, "Enter Name: ", InputUtil.MAX_NAME_LENGTH);
        String email = InputUtil.readEmail(scanner, "Enter Email (.com required): ");
        String password = InputUtil.readPassword(scanner, "Enter Password: ");

        // avoid duplicate email registration
        for (User u : allUsers) {
            if (u.getEmail().equalsIgnoreCase(email)) {
                System.out.println("✗ Email already registered. Try login.");
                return;
            }
        }

        switch (userType) {
            case 1:
                String studentId = InputUtil.readId(scanner, "Enter Student ID: ");
                String department = InputUtil.readNonEmpty(scanner, "Enter Department: ", 40);
                int semester = InputUtil.readIntRange(scanner, "Enter Semester (1-12): ", 1, 12);
                String skillsInput = InputUtil.readOptional(scanner, "Enter Skills (comma separated, optional): ", 150);

                Student student = new Student(studentId, name, email, password, department, semester);
                if (!skillsInput.trim().isEmpty()) {
                    for (String skill : skillsInput.split(",")) student.addSkill(skill.trim());
                }

                students.add(student);
                allUsers.add(student);
                saveUsersToDisk();

                System.out.println("Student registered successfully! Please login.");
                SystemLogger.log("Student registered: " + name + " (" + email + ")");
                break;

            case 2:
                String adminId = InputUtil.readId(scanner, "Enter Admin ID: ");
                SocietyAdmin societyAdmin = new SocietyAdmin(adminId, name, email, password);
                societyAdmins.add(societyAdmin);
                allUsers.add(societyAdmin);
                saveUsersToDisk();

                System.out.println("Society Admin registered successfully! Please login.");
                SystemLogger.log("SocietyAdmin registered: " + name + " (" + email + ")");
                break;

            case 3:
                String deptId = InputUtil.readId(scanner, "Enter Department Rep ID: ");
                String dept = InputUtil.readNonEmpty(scanner, "Enter Department: ", 40);
                DepartmentRepresentative deptRep = new DepartmentRepresentative(deptId, name, email, password, dept);
                departmentReps.add(deptRep);
                allUsers.add(deptRep);
                saveUsersToDisk();

                System.out.println("Department Representative registered successfully! Please login.");
                SystemLogger.log("DeptRep registered: " + name + " (" + email + ")");
                break;

            case 4:
                String sysAdminId = InputUtil.readId(scanner, "Enter System Admin ID: ");
                SystemAdmin systemAdmin = new SystemAdmin(sysAdminId, name, email, password);
                systemAdmins.add(systemAdmin);
                allUsers.add(systemAdmin);
                saveUsersToDisk();

                System.out.println("System Admin registered successfully! Please login.");
                SystemLogger.log("SystemAdmin registered: " + name + " (" + email + ")");
                break;
        }
    }

    private void loginUser(Scanner scanner) {
        System.out.println("\n=== LOGIN ===");
        String email = InputUtil.readNonEmpty(scanner, "Enter Email: ", InputUtil.MAX_EMAIL_LENGTH);
        String password = InputUtil.readNonEmpty(scanner, "Enter Password: ", InputUtil.MAX_PASS_LENGTH);

        User loggedInUser = null;

        for (User user : allUsers) {
            if (user.getEmail().equalsIgnoreCase(email) && user.getPassword().equals(password)) {
                if (user.isActive()) {
                    loggedInUser = user;
                    break;
                } else {
                    System.out.println("Account is deactivated. Contact system admin.");
                    return;
                }
            }
        }

        if (loggedInUser != null) {
            System.out.println("\nLogin successful! Welcome " + loggedInUser.getName() + "!");
            SystemLogger.log("Login success: " + loggedInUser.getName() + " (" + loggedInUser.getRole() + ")");
            loggedInUser.showMenu(scanner);
        } else {
            System.out.println("Invalid email or password!");
            SystemLogger.log("Login failed for email: " + email);
        }
    }

    // Helper methods
    public Society findSocietyByName(String name) {
        for (Society s : societies) {
            if (s.getName().equalsIgnoreCase(name)) return s;
        }
        return null;
    }

    // ====== PERSISTENCE (USERS DB) ======
private static final String USERS_DB_FILE = "cuiconnect_users.txt";

private void addUserToLists(User u) {
    allUsers.add(u);
    if (u instanceof Student) students.add((Student) u);
    else if (u instanceof SocietyAdmin) societyAdmins.add((SocietyAdmin) u);
    else if (u instanceof DepartmentRepresentative) departmentReps.add((DepartmentRepresentative) u);
    else if (u instanceof SystemAdmin) systemAdmins.add((SystemAdmin) u);
}

private void clearAllUsers() {
    allUsers.clear();
    students.clear();
    societyAdmins.clear();
    departmentReps.clear();
    systemAdmins.clear();
}

// Save ALL current users to file (overwrite to avoid duplicates)
public void saveUsersToDisk() {
    FileHandler fh = new FileHandler(USERS_DB_FILE);
    StringBuilder sb = new StringBuilder();
    sb.append("# CUICONNECT USERS DB\n");
    // Format: ROLE|id|name|email|pass|active|extra...

    for (User u : allUsers) {
        if (u instanceof Student s) {
            sb.append("STUDENT|")
              .append(escape(s.getStudentId())).append("|")
              .append(escape(s.getName())).append("|")
              .append(escape(s.getEmail())).append("|")
              .append(escape(s.getPassword())).append("|")
              .append(s.isActive()).append("|")
              .append(escape(s.getDepartment())).append("|")
              .append(s.getSemester()).append("|")
              .append(escape(String.join(",", s.getSkills())))
              .append("\n");
        } else if (u instanceof SocietyAdmin a) {
            sb.append("SOCIETY_ADMIN|")
              .append(escape(a.getAdminId())).append("|")
              .append(escape(a.getName())).append("|")
              .append(escape(a.getEmail())).append("|")
              .append(escape(a.getPassword())).append("|")
              .append(a.isActive())
              .append("\n");
        } else if (u instanceof DepartmentRepresentative d) {
            sb.append("DEPARTMENT_REP|")
              .append(escape(d.getDeptId())).append("|")
              .append(escape(d.getName())).append("|")
              .append(escape(d.getEmail())).append("|")
              .append(escape(d.getPassword())).append("|")
              .append(d.isActive()).append("|")
              .append(escape(d.getDepartment()))
              .append("\n");
        } else if (u instanceof SystemAdmin sa) {
            sb.append("SYSTEM_ADMIN|")
              .append(escape(extractRawId(sa.getUserId(), "SYS_"))).append("|")
              .append(escape(sa.getName())).append("|")
              .append(escape(sa.getEmail())).append("|")
              .append(escape(sa.getPassword())).append("|")
              .append(sa.isActive())
              .append("\n");
        }
    }

    fh.overwriteToFile(sb.toString());
    SystemLogger.log("Saved users DB to " + USERS_DB_FILE);
}

// Load users from file into memory
public void loadUsersFromDisk() {
    FileHandler fh = new FileHandler(USERS_DB_FILE);
    List<String> lines = fh.readLines();
    if (lines.isEmpty()) return;

    clearAllUsers();

    int loaded = 0;
    for (String line : lines) {
        if (line.startsWith("#")) continue;

        String[] p = line.split("\\|", -1);
        if (p.length < 6) continue;

        String role = p[0].trim();
        try {
            switch (role) {
                case "STUDENT": {
                    // STUDENT|studentId|name|email|pass|active|dept|semester|skills
                    if (p.length < 9) break;

                    String studentId = unescape(p[1]);
                    String name = unescape(p[2]);
                    String email = unescape(p[3]);
                    String pass = unescape(p[4]);
                    boolean active = Boolean.parseBoolean(p[5]);
                    String dept = unescape(p[6]);
                    int sem = Integer.parseInt(p[7]);
                    String skillsCsv = unescape(p[8]);

                    Student s = new Student(studentId, name, email, pass, dept, sem);
                    s.isActive = active;

                    if (!skillsCsv.trim().isEmpty()) {
                        for (String sk : skillsCsv.split(",")) s.addSkill(sk.trim());
                    }

                    addUserToLists(s);
                    loaded++;
                    break;
                }

                case "SOCIETY_ADMIN": {
                    // SOCIETY_ADMIN|adminId|name|email|pass|active
                    String adminId = unescape(p[1]);
                    String name = unescape(p[2]);
                    String email = unescape(p[3]);
                    String pass = unescape(p[4]);
                    boolean active = Boolean.parseBoolean(p[5]);

                    SocietyAdmin a = new SocietyAdmin(adminId, name, email, pass);
                    a.isActive = active;
                    addUserToLists(a);
                    loaded++;
                    break;
                }

                case "DEPARTMENT_REP": {
                    // DEPARTMENT_REP|deptId|name|email|pass|active|department
                    if (p.length < 7) break;

                    String deptId = unescape(p[1]);
                    String name = unescape(p[2]);
                    String email = unescape(p[3]);
                    String pass = unescape(p[4]);
                    boolean active = Boolean.parseBoolean(p[5]);
                    String dept = unescape(p[6]);

                    DepartmentRepresentative d = new DepartmentRepresentative(deptId, name, email, pass, dept);
                    d.isActive = active;
                    addUserToLists(d);
                    loaded++;
                    break;
                }

                case "SYSTEM_ADMIN": {
                    // SYSTEM_ADMIN|id|name|email|pass|active
                    String id = unescape(p[1]);
                    String name = unescape(p[2]);
                    String email = unescape(p[3]);
                    String pass = unescape(p[4]);
                    boolean active = Boolean.parseBoolean(p[5]);

                    SystemAdmin sa = new SystemAdmin(id, name, email, pass);
                    sa.isActive = active;
                    addUserToLists(sa);
                    loaded++;
                    break;
                }
            }
        } catch (Exception ignored) {
            // ignore bad lines to avoid crash
        }
    }

    if (loaded > 0) {
        System.out.println("✅ Loaded " + loaded + " users from " + USERS_DB_FILE);
        SystemLogger.log("Loaded " + loaded + " users from disk.");
    }
}

// Helpers for safe delimiter storage
private static String escape(String s) {
    if (s == null) return "";
    return s.replace("\\", "\\\\").replace("|", "\\|").replace("\n", " ");
}
private static String unescape(String s) {
    if (s == null) return "";
    return s.replace("\\|", "|").replace("\\\\", "\\");
}
private static String extractRawId(String userId, String prefix) {
    if (userId != null && userId.startsWith(prefix)) return userId.substring(prefix.length());
    return (userId == null ? "" : userId);
}


    public Group findGroupByName(String name) {
        for (Group g : groups) {
            if (g.getName().equalsIgnoreCase(name)) return g;
        }
        return null;
    }

    public void addSociety(Society society) { societies.add(society); }
    public void addEvent(Event event) { events.add(event); }
    public void addGroup(Group group) { groups.add(group); }

    // Getters
    public List<User> getAllUsers() { return allUsers; }
    public List<Student> getAllStudents() { return students; }
    public List<Society> getAllSocieties() { return societies; }
    public List<Event> getAllEvents() { return events; }
    public List<Group> getAllGroups() { return groups; }

    public int getActiveUsersCount() {
        int count = 0;
        for (User u : allUsers) if (u.isActive()) count++;
        return count;
    }

    // Main method
    public static void main(String[] args) {
        CuiConnect system = CuiConnect.getInstance();
        Scanner sc = new Scanner(System.in);
        system.loadUsersFromDisk();

        System.out.println("Do you want to load sample data for quick testing? (y/n)");
        boolean load = InputUtil.readYesNo(sc, "Choice: ");
        if (load) {
            system.getAllUsers().clear();
            system.getAllStudents().clear();
            initializeSampleData();
        }
        system.startSystem(sc);
        sc.close();
    }

    // Sample data for all modules (society, events, groups, group chat)
    private static void initializeSampleData() {
        CuiConnect system = CuiConnect.getInstance();

        Student student1 = new Student("FA20-BCS-001", "Ali Khan", "ali.student@demo.com", "pass123", "Computer Science", 5);
        student1.addSkill("Java");
        student1.addSkill("Python");
        student1.addSkill("Database");

        Student student2 = new Student("FA20-BCS-002", "Sara Ahmed", "sara.student@demo.com", "pass123", "Computer Science", 5);
        student2.addSkill("Web Development");
        student2.addSkill("JavaScript");
        student2.addSkill("React");

        Student student3 = new Student("FA20-BBA-001", "Ahmed Raza", "ahmed.student@demo.com", "pass123", "Business", 4);
        student3.addSkill("Marketing");
        student3.addSkill("Management");

        system.students.add(student1);
        system.students.add(student2);
        system.students.add(student3);
        system.allUsers.add(student1);
        system.allUsers.add(student2);
        system.allUsers.add(student3);

        SocietyAdmin admin1 = new SocietyAdmin("SA001", "Dr. Farhan", "farhan.admin@demo.com", "admin123");
        system.societyAdmins.add(admin1);
        system.allUsers.add(admin1);

        Society csSociety = new Society("CS Society", "Computer Science Student Society", "Academic", admin1);
        Society sportsSociety = new Society("Sports Society", "Sports & Fitness Community", "Sports", admin1);
        Society aiSociety = new Society("AI Society", "AI, ML, and Research Community", "Tech", admin1);

        admin1.addManagedSociety(csSociety);
        admin1.addManagedSociety(sportsSociety);
        admin1.addManagedSociety(aiSociety);

        system.societies.add(csSociety);
        system.societies.add(sportsSociety);
        system.societies.add(aiSociety);

        csSociety.getMembers().add(student1);
        csSociety.getMembers().add(student2);
        student1.getJoinedSocieties().add(csSociety);
        student2.getJoinedSocieties().add(csSociety);

        Event event1 = new Event("Java Workshop", "Learn advanced Java programming",
                LocalDate.now().plusDays(7), "CS Lab 5", "CS Society");
        Event event2 = new Event("Career Fair", "Meet tech companies",
                LocalDate.now().plusDays(14), "Auditorium", "Career Office");
        Event event3 = new Event("AI Bootcamp", "Intro to ML + projects",
                LocalDate.now().plusDays(10), "Seminar Hall", "AI Society");

        system.events.add(event1);
        system.events.add(event2);
        system.events.add(event3);

        DepartmentRepresentative deptRep = new DepartmentRepresentative("DR001", "Dr. Sana",
                "sana.rep@demo.com", "dept123", "Computer Science");
        system.departmentReps.add(deptRep);
        system.allUsers.add(deptRep);

        SystemAdmin sysAdmin = new SystemAdmin("SYS001", "Admin", "admin.sys@demo.com", "sysadmin");
        system.systemAdmins.add(sysAdmin);
        system.allUsers.add(sysAdmin);

        // Groups + sample chat
        Group g1 = new Group("OOP Study Group", "Discuss OOP concepts and viva prep", "Academic", student1);
        g1.join(student2);
        g1.join(student3);
        g1.postMessage(new Message("Assalam o Alaikum, let's prepare OOP viva together.", student1, g1));
        g1.postMessage(new Message("Walaikum Salam! I'll share SOLID principles notes.", student2, g1));

        Group g2 = new Group("AI Project Team", "Discuss AI project report & demo", "Tech", student2);
        g2.join(student1);
        g2.join(student3);
        g2.postMessage(new Message("Guys, add input validation + sample data for demo.", student2, g2));
        g2.postMessage(new Message("I fixed group join and logs module.", student1, g2));
        g2.postMessage(new Message("I'll write final report intro and UML summary.", student3, g2));

        system.groups.add(g1);
        system.groups.add(g2);

        student1.sendNotification("Welcome! Sample data loaded.");
        student2.sendNotification("You have been added to AI Project Team group.");
        student3.sendNotification("Try joining a society from the list.");

        SystemLogger.log("Sample data initialized.");
        System.out.println("\n✅ Sample data initialized successfully!");
        System.out.println("Login credentials:");
        System.out.println("Student: ali.student@demo.com / pass123");
        System.out.println("Society Admin: farhan.admin@demo.com / admin123");
        System.out.println("Department Rep: sana.rep@demo.com / dept123");
        System.out.println("System Admin: admin.sys@demo.com / sysadmin");
    }
}
