import { Navigate } from "react-router-dom";
import { useUser } from "@/contexts/UserContext";

const Index = () => {
  const { isAuthenticated } = useUser();

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />;
  }

  return <Navigate to="/" replace />;
};

export default Index;